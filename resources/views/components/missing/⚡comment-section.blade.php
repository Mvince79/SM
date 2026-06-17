<?php

use Livewire\Component;
use App\Models\Comment;
use App\Models\MissingChild;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public int    $caseId     = 0;
    public string $body       = '';
    public string $replyBody  = '';
    public ?int   $replyingTo = null;
    public ?int   $editingId  = null;
    public string $editBody   = '';

    public function mount(int $caseId): void
    {
        $this->caseId = $caseId;
    }

    public function getCommentsProperty()
    {
        return Comment::where('commentable_type', MissingChild::class)
            ->where('commentable_id', $this->caseId)
            ->whereNull('parent_id')
            ->where('is_approved', 1)
            ->with(['user', 'replies' => function ($q) {
                $q->where('is_approved', 1)->with('user')->oldest();
            }])
            ->latest()
            ->get();
    }

    public function submit(): void
    {
        if (!Auth::check()) return;

        $this->validate([
            'body' => ['required', 'string', 'min:2', 'max:1000'],
        ], [
            'body.required' => 'Vui lòng nhập nội dung bình luận.',
            'body.min'      => 'Bình luận cần ít nhất 2 ký tự.',
            'body.max'      => 'Bình luận tối đa 1000 ký tự.',
        ]);

        $comment = Comment::create([
            'commentable_type' => MissingChild::class,
            'commentable_id'   => $this->caseId,
            'user_id'          => Auth::id(),
            'parent_id'        => null,
            'body'             => $this->body,
            'is_approved'      => 1,
            'is_hidden'        => 0,
        ]);

        $this->body = '';
    }

    public function submitReply(): void
    {
        if (!Auth::check() || !$this->replyingTo) return;

        $this->validate([
            'replyBody' => ['required', 'string', 'min:2', 'max:1000'],
        ], [
            'replyBody.required' => 'Vui lòng nhập nội dung phản hồi.',
        ]);

        $reply = Comment::create([
            'commentable_type' => MissingChild::class,
            'commentable_id'   => $this->caseId,
            'user_id'          => Auth::id(),
            'parent_id'        => $this->replyingTo,
            'body'             => $this->replyBody,
            'is_approved'      => 1,
            'is_hidden'        => 0,
        ]);

        $this->replyBody  = '';
        $this->replyingTo = null;
    }

    public function toggleReply(int $commentId): void
    {
        $this->replyingTo = ($this->replyingTo === $commentId) ? null : $commentId;
        $this->replyBody  = '';
        $this->editingId  = null;
    }

    public function hideComment(int $commentId): void
    {
        if (!Auth::check()) return;
        $comment = Comment::find($commentId);
        if (!$comment || $comment->user_id !== Auth::id()) return;
        $comment->update(['is_hidden' => 1]);
    }

    public function startEdit(int $commentId): void
    {
        if (!Auth::check()) return;
        $comment = Comment::find($commentId);
        if (!$comment || $comment->user_id !== Auth::id()) return;
        $this->editingId  = $commentId;
        $this->editBody   = $comment->body;
        $this->replyingTo = null;
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->editBody  = '';
    }

    public function saveEdit(): void
    {
        if (!Auth::check() || !$this->editingId) return;

        $this->validate([
            'editBody' => ['required', 'string', 'min:2', 'max:1000'],
        ], [
            'editBody.required' => 'Vui lòng nhập nội dung.',
        ]);

        $comment = Comment::find($this->editingId);
        if (!$comment || $comment->user_id !== Auth::id()) return;

        $comment->update(['body' => $this->editBody]);
        $this->editingId = null;
        $this->editBody  = '';
    }
};
?>

<div class="space-y-6"
     x-data="{ wireId: null }"
     x-init="
        wireId = $el.closest('[wire\\:id]')?.getAttribute('wire:id');
        if (window.Echo && wireId) {
            window.Echo.channel('missing-child.{{ $caseId }}')
                .listen('.comment.hidden', () => {
                    Livewire.find(wireId).$refresh();
                })
                ;
        }
     ">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h3 class="font-extrabold text-slate-800 text-base">
            <x-heroicon-o-chat-bubble-left-right class="w-5 h-5 inline" /> Bình luận ({{ $this->comments->count() }})
        </h3>
    </div>

    {{-- Form gửi comment mới --}}
    @auth
        <div class="flex gap-3 items-start">
            <img src="{{ Auth::user()->avatar_url }}"
                 class="w-9 h-9 rounded-full object-cover border border-slate-200 flex-shrink-0 bg-slate-100"
                 alt="{{ Auth::user()->full_name }}"/>
            <div class="flex-1 space-y-2">
                <textarea wire:model="body" rows="2" placeholder="Viết bình luận..."
                          class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent bg-slate-50 focus:bg-white transition-all font-medium resize-none placeholder-slate-400
                                 {{ $errors->has('body') ? 'border-red-400' : '' }}"></textarea>
                @error('body')
                    <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
                @enderror
                <div class="flex justify-end">
                    <button wire:click="submit" wire:loading.attr="disabled"
                            class="bg-primary text-white text-xs font-extrabold px-5 py-2.5 rounded-xl hover:bg-primary-dark transition-colors shadow-sm disabled:opacity-60">
                        <span wire:loading.remove wire:target="submit">Gửi bình luận</span>
                        <span wire:loading wire:target="submit">Đang gửi...</span>
                    </button>
                </div>
            </div>
        </div>
    @else
        <div class="bg-slate-50 border border-slate-200 rounded-2xl px-5 py-4 text-center">
            <p class="text-xs text-slate-500 font-semibold">
                <a href="{{ route('auth.login') }}" class="text-primary font-extrabold hover:underline">Đăng nhập</a>
                để gửi bình luận
            </p>
        </div>
    @endauth

    {{-- Danh sách comments --}}
    @if($this->comments->isEmpty())
        <p class="text-xs text-slate-400 font-semibold text-center py-6">
            Chưa có bình luận nào. Hãy là người đầu tiên!
        </p>
    @else
        <div class="space-y-5">
            @foreach($this->comments as $comment)
                <div class="flex gap-3 items-start">
                    <img src="{{ $comment->user->avatar_url }}"
                         class="w-9 h-9 rounded-full object-cover border border-slate-200 flex-shrink-0 bg-slate-100"
                         alt="{{ $comment->user->full_name }}"/>

                    <div class="flex-1 space-y-1.5">

                        {{-- Form edit inline --}}
                        @if($editingId === $comment->id)
                            <div class="space-y-2">
                                <textarea wire:model="editBody" rows="2"
                                          class="w-full px-4 py-3 border border-primary/40 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-medium resize-none"></textarea>
                                @error('editBody')
                                    <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
                                @enderror
                                <div class="flex gap-2">
                                    <button wire:click="saveEdit"
                                            class="bg-primary text-white text-xs font-extrabold px-4 py-2 rounded-xl hover:bg-primary-dark transition-colors">
                                        Lưu
                                    </button>
                                    <button wire:click="cancelEdit"
                                            class="text-xs font-bold text-slate-500 px-4 py-2 rounded-xl hover:bg-slate-100 transition-colors">
                                        Hủy
                                    </button>
                                </div>
                            </div>
                        @else
                            {{-- Bubble --}}
                            <div class="bg-slate-100 rounded-2xl px-4 py-3 inline-block max-w-full
                                        {{ $comment->is_hidden ? 'opacity-50' : '' }}">
                                <span class="text-xs font-extrabold text-slate-900">
                                    {{ $comment->user->full_name }}
                                </span>
                                @if($comment->is_hidden)
                                    <p class="text-xs text-slate-400 italic mt-0.5">[Bình luận đã bị ẩn]</p>
                                @else
                                    <p class="text-sm text-slate-700 leading-relaxed mt-0.5 font-medium">
                                        {{ $comment->body }}
                                    </p>
                                @endif
                            </div>
                        @endif

                        {{-- Actions + Confirm xóa bằng Alpine --}}
                        @if($editingId !== $comment->id)
                            <div x-data="{ confirmDelete: false }"
                                 class="pl-1">

                                {{-- Row 1: timestamp + actions --}}
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[10px] font-bold text-slate-400">

                                    <span>{{ $comment->created_at->diffForHumans() }}</span>

                                    @auth
                                        @if(!$comment->is_hidden)
                                            <button wire:click="toggleReply({{ $comment->id }})"
                                                    class="hover:text-primary transition-colors">
                                                {{ $replyingTo === $comment->id ? 'Hủy trả lời' : 'Trả lời' }}
                                            </button>
                                        @endif

                                        @if(Auth::id() === $comment->user_id && !$comment->is_hidden)
                                            <button wire:click="startEdit({{ $comment->id }})"
                                                    class="hover:text-brand transition-colors">
                                                Chỉnh sửa
                                            </button>

                                            <button x-show="!confirmDelete"
                                                    @click="confirmDelete = true"
                                                    class="hover:text-red-500 transition-colors">
                                                Xóa
                                            </button>
                                        @endif
                                    @endauth
                                </div>

                                {{-- Confirm xóa — xuống dòng riêng, thân thiện mobile --}}
                                @if(Auth::id() === $comment->user_id && !$comment->is_hidden)
                                    <div x-show="confirmDelete"
                                         x-transition
                                         class="mt-2 flex flex-wrap items-center gap-2 bg-red-50 border border-red-200 rounded-xl px-3 py-2">
                                        <span class="text-[10px] text-red-600 font-extrabold">Xóa bình luận này?</span>
                                        <div class="flex gap-2">
                                            <button wire:click="hideComment({{ $comment->id }})"
                                                    @click="confirmDelete = false"
                                                    class="bg-red-500 text-white font-extrabold px-3 py-1.5 rounded-lg hover:bg-red-600 transition-colors text-[10px]">
                                                Xác nhận
                                            </button>
                                            <button @click="confirmDelete = false"
                                                    class="text-slate-500 font-bold px-3 py-1.5 rounded-lg hover:bg-slate-100 transition-colors text-[10px] bg-white border border-slate-200">
                                                Hủy
                                            </button>
                                        </div>
                                    </div>
                                @endif

                            </div>
                        @endif

                        {{-- Form reply inline --}}
                        @if($replyingTo === $comment->id)
                            <div class="flex gap-2 items-start ml-6 mt-2">
                                <img src="{{ Auth::user()->avatar_url }}"
                                     class="w-7 h-7 rounded-full object-cover border border-slate-200 flex-shrink-0 bg-slate-100"/>
                                <div class="flex-1 space-y-2">
                                    <textarea wire:model="replyBody" rows="2" placeholder="Viết phản hồi..."
                                              class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-medium resize-none placeholder-slate-400
                                                     {{ $errors->has('replyBody') ? 'border-red-400' : '' }}"></textarea>
                                    @error('replyBody')
                                        <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
                                    @enderror
                                    <div class="flex gap-2">
                                        <button wire:click="submitReply" wire:loading.attr="disabled"
                                                class="bg-primary text-white text-xs font-extrabold px-4 py-2 rounded-xl hover:bg-primary-dark transition-colors disabled:opacity-60">
                                            <span wire:loading.remove wire:target="submitReply">Gửi</span>
                                            <span wire:loading wire:target="submitReply">...</span>
                                        </button>
                                        <button wire:click="toggleReply({{ $comment->id }})"
                                                class="text-xs font-bold text-slate-500 px-4 py-2 rounded-xl hover:bg-slate-100 transition-colors">
                                            Hủy
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Replies --}}
                        @if($comment->replies->isNotEmpty())
                            <div class="ml-6 space-y-3 mt-2">
                                @foreach($comment->replies as $reply)
                                    <div class="flex gap-3 items-start">
                                        <img src="{{ $reply->user->avatar_url }}"
                                             class="w-7 h-7 rounded-full object-cover border border-slate-200 flex-shrink-0 bg-slate-100"/>
                                        <div class="flex-1">

                                            {{-- Edit form cho reply --}}
                                            @if($editingId === $reply->id)
                                                <div class="space-y-2">
                                                    <textarea wire:model="editBody" rows="2"
                                                              class="w-full px-3 py-2 border border-primary/40 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all font-medium resize-none"></textarea>
                                                    @error('editBody')
                                                        <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
                                                    @enderror
                                                    <div class="flex gap-2">
                                                        <button wire:click="saveEdit"
                                                                class="bg-primary text-white text-xs font-extrabold px-4 py-2 rounded-xl hover:bg-primary-dark transition-colors">
                                                            Lưu
                                                        </button>
                                                        <button wire:click="cancelEdit"
                                                                class="text-xs font-bold text-slate-500 px-4 py-2 rounded-xl hover:bg-slate-100 transition-colors">
                                                            Hủy
                                                        </button>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="bg-slate-100 rounded-2xl px-3 py-2.5 inline-block max-w-full
                                                            {{ $reply->is_hidden ? 'opacity-50' : '' }}">
                                                    <span class="text-xs font-extrabold text-slate-900">
                                                        {{ $reply->user->full_name }}
                                                    </span>
                                                    @if($reply->is_hidden)
                                                        <p class="text-xs text-slate-400 italic mt-0.5">[Bình luận đã bị ẩn]</p>
                                                    @else
                                                        <p class="text-sm text-slate-700 leading-relaxed mt-0.5 font-medium">
                                                            {{ $reply->body }}
                                                        </p>
                                                    @endif
                                                </div>
                                            @endif

                                            {{-- Reply actions --}}
                                            @if($editingId !== $reply->id)
                                                <div x-data="{ confirmDelete: false }" class="mt-1 pl-1">

                                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[10px] font-bold text-slate-400">
                                                        <span>{{ $reply->created_at->diffForHumans() }}</span>

                                                        @auth
                                                            @if(Auth::id() === $reply->user_id && !$reply->is_hidden)
                                                                {{-- FIX: Chỉnh sửa reply của chính mình --}}
                                                                <button wire:click="startEdit({{ $reply->id }})"
                                                                        class="hover:text-brand transition-colors">
                                                                    Chỉnh sửa
                                                                </button>

                                                                <button x-show="!confirmDelete"
                                                                        @click="confirmDelete = true"
                                                                        class="hover:text-red-500 transition-colors">
                                                                    Xóa
                                                                </button>
                                                            @endif
                                                        @endauth
                                                    </div>

                                                    {{-- Confirm xóa reply — mobile-friendly --}}
                                                    @auth
                                                        @if(Auth::id() === $reply->user_id && !$reply->is_hidden)
                                                            <div x-show="confirmDelete"
                                                                 x-transition
                                                                 class="mt-2 flex flex-wrap items-center gap-2 bg-red-50 border border-red-200 rounded-xl px-3 py-2">
                                                                <span class="text-[10px] text-red-600 font-extrabold">Xóa bình luận này?</span>
                                                                <div class="flex gap-2">
                                                                    <button wire:click="hideComment({{ $reply->id }})"
                                                                            @click="confirmDelete = false"
                                                                            class="bg-red-500 text-white font-extrabold px-3 py-1.5 rounded-lg hover:bg-red-600 transition-colors text-[10px]">
                                                                        Xác nhận
                                                                    </button>
                                                                    <button @click="confirmDelete = false"
                                                                            class="text-slate-500 font-bold px-3 py-1.5 rounded-lg hover:bg-slate-100 transition-colors text-[10px] bg-white border border-slate-200">
                                                                        Hủy
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    @endauth

                                                </div>
                                            @endif

                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>