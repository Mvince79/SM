<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Clue;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithFileUploads;

    public int    $missingId   = 0;
    public string $description = '';
    public string $lat         = '';
    public string $lng         = '';
    public bool   $open        = false;

    // Reply
    public string $replyBody  = '';
    public ?int   $replyingTo = null;

    // Edit
    public ?int   $editingId  = null;
    public string $editBody   = '';

    public function mount(int $missingId): void
    {
        $this->missingId = $missingId;
    }

    public function getCluesProperty()
    {
        return Clue::where('missing_id', $this->missingId)
            ->whereNull('parent_id')
            ->with(['user', 'replies.user'])
            ->latest()
            ->get();
    }

    public function toggle(): void
    {
        $this->open = !$this->open;
        if (!$this->open) {
            $this->reset(['description', 'lat', 'lng']);
        }
    }

    public function updateGPS(string $lat, string $lng): void
    {
        $this->lat = $lat;
        $this->lng = $lng;
    }

    public function submit(): void
    {
        if (!Auth::check()) return;

        $this->validate(
            [
                'description' => ['required', 'string', 'min:10', 'max:2000'],
                'lat'         => ['nullable', 'numeric', 'between:-90,90'],
                'lng'         => ['nullable', 'numeric', 'between:-180,180'],
            ],
            [
                'description.required' => 'Vui lòng mô tả thông tin bạn biết.',
                'description.min'      => 'Mô tả cần ít nhất 10 ký tự.',
            ],
        );

        Clue::create([
            'missing_id'  => $this->missingId,
            'parent_id'   => null,
            'user_id'     => Auth::id(),
            'description' => $this->description,
            'lat'         => $this->lat ?: null,
            'lng'         => $this->lng ?: null,
            'clue_type'   => 'SIGHTING',
            'is_verified' => 0,
            'is_anonymous'=> 0,
        ]);

        $this->reset(['description', 'lat', 'lng']);
        $this->open = false;
    }

    // ── Reply ────────────────────────────────────────────────────────────────

    public function toggleReply(int $clueId): void
    {
        $this->replyingTo = ($this->replyingTo === $clueId) ? null : $clueId;
        $this->replyBody  = '';
        $this->editingId  = null;
    }

    public function submitReply(): void
    {
        if (!Auth::check() || !$this->replyingTo) return;

        $this->validate(
            ['replyBody' => ['required', 'string', 'min:2', 'max:1000']],
            ['replyBody.required' => 'Vui lòng nhập nội dung phản hồi.'],
        );

        Clue::create([
            'missing_id'  => $this->missingId,
            'parent_id'   => $this->replyingTo,
            'user_id'     => Auth::id(),
            'description' => $this->replyBody,
            'clue_type'   => 'SIGHTING',
            'is_verified' => 0,
            'is_anonymous'=> 0,
        ]);

        $this->replyBody  = '';
        $this->replyingTo = null;
    }

    // ── Edit ─────────────────────────────────────────────────────────────────

    public function startEdit(int $clueId): void
    {
        if (!Auth::check()) return;
        $clue = Clue::find($clueId);
        if (!$clue || $clue->user_id !== Auth::id()) return;

        $this->editingId  = $clueId;
        $this->editBody   = $clue->description;
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

        $this->validate(
            ['editBody' => ['required', 'string', 'min:10', 'max:2000']],
            ['editBody.required' => 'Vui lòng nhập nội dung.'],
        );

        $clue = Clue::find($this->editingId);
        if (!$clue || $clue->user_id !== Auth::id()) return;

        $clue->update(['description' => $this->editBody]);
        $this->editingId = null;
        $this->editBody  = '';
    }

    // ── Delete ───────────────────────────────────────────────────────────────

    public function deleteClue(int $clueId): void
    {
        if (!Auth::check()) return;
        $clue = Clue::find($clueId);
        if (!$clue || $clue->user_id !== Auth::id()) return;
        $clue->delete();
    }
};
?>

<div>
    {{-- Header row --}}
    <div class="relative flex items-center justify-between gap-3 border-b border-slate-100 pb-3 sm:pb-4">
        <h3 class="font-black text-slate-900 text-sm sm:text-lg tracking-tight">
            Manh mối từ cộng đồng
            <span class="text-primary">({{ $this->clues->count() }})</span>
        </h3>

        @auth
            @if (!$open)
                <button wire:click="toggle"
                    class="text-xs font-black text-primary hover:text-primary-dark uppercase tracking-wider transition-colors whitespace-nowrap">
                    + Gửi manh mối mới
                </button>
            @endif
        @else
            <a href="{{ route('auth.login') }}"
                class="text-xs font-black text-primary hover:text-primary-dark uppercase tracking-wider transition-colors">
                Đăng nhập để gửi manh mối
            </a>
        @endauth
    </div>

    {{-- Form gửi mới --}}
    @if ($open)
        <div class="mt-4 bg-slate-50 rounded-2xl p-4 sm:p-5 border border-slate-200 space-y-4 shadow-sm"
            x-data="{
                getGPS() {
                    if (!navigator.geolocation) { alert('Trình duyệt không hỗ trợ GPS'); return; }
                    navigator.geolocation.getCurrentPosition(
                        pos => $wire.updateGPS(pos.coords.latitude.toString(), pos.coords.longitude.toString()),
                        () => alert('Không lấy được vị trí. Hãy cho phép truy cập GPS.')
                    );
                }
            }">
            <h4 class="font-extrabold text-slate-800 text-sm">Gửi manh mối của bạn</h4>

            <textarea wire:model="description" rows="3"
                placeholder="Ghi cụ thể những gì bạn thấy, thời gian, đặc điểm trang phục bé lúc đó..."
                class="w-full px-4 py-3 border border-slate-200 rounded-2xl text-xs focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent bg-white resize-none {{ $errors->has('description') ? 'border-red-400' : '' }}"></textarea>
            @error('description')
                <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
            @enderror

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" @click="getGPS()"
                    class="inline-flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-3 py-2 rounded-xl border border-slate-200 transition-colors">
                    📍 Gửi toạ độ GPS
                </button>
                @if ($lat && $lng)
                    <span class="text-xs text-success font-bold">
                        ✅ {{ number_format($lat, 4) }}, {{ number_format($lng, 4) }}
                    </span>
                @endif
                <div class="flex-1"></div>
                <button wire:click="toggle"
                    class="text-xs font-bold text-slate-500 px-4 py-2 rounded-xl hover:bg-slate-100 transition-colors">
                    Hủy bỏ
                </button>
                <button wire:click="submit" wire:loading.attr="disabled"
                    class="bg-primary text-white text-xs font-extrabold px-5 py-2 rounded-xl hover:bg-primary-dark transition-all shadow-sm disabled:opacity-60">
                    <span wire:loading.remove wire:target="submit">Gửi thông tin</span>
                    <span wire:loading wire:target="submit">Đang gửi...</span>
                </button>
            </div>
        </div>
    @endif

    {{-- Danh sách clue --}}
    <div class="space-y-4 sm:space-y-6 mt-4 sm:mt-6">
        @forelse($this->clues as $clue)
            <div class="flex gap-2.5 sm:gap-3.5 items-start">
                {{-- Avatar --}}
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full flex-shrink-0 flex items-center justify-center font-bold text-xs
                    {{ $clue->is_verified ? 'bg-brand/10 border border-brand/20 text-brand' : 'bg-slate-100 border border-slate-200 text-slate-500' }}">
                    {{ $clue->initials }}
                </div>

                <div class="flex-1 min-w-0 space-y-1.5 sm:space-y-2">

                    {{-- Edit form inline --}}
                    @if($editingId === $clue->id)
                        <div class="space-y-2">
                            <textarea wire:model="editBody" rows="3"
                                class="w-full px-4 py-3 border border-primary/40 rounded-2xl text-xs focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all resize-none"></textarea>
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
                        <div class="bg-slate-100 p-3 sm:p-4 rounded-2xl inline-block max-w-full {{ $clue->is_verified ? 'border-l-4 border-success' : '' }}">
                            <div class="flex items-center gap-1.5 sm:gap-2 mb-1 flex-wrap">
                                <span class="text-xs font-extrabold text-slate-900">{{ $clue->display_name }}</span>
                                @if ($clue->is_verified)
                                    <span class="bg-success-light text-success border border-success/35 text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full">Đã xác minh ✓</span>
                                @else
                                    <span class="bg-amber-50 text-amber-700 border border-amber-200 text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full">Đang xác minh</span>
                                @endif
                                <span class="text-[9px] sm:text-[10px] text-slate-400 font-bold">{{ $clue->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed font-medium break-words">{{ $clue->description }}</p>
                            @if ($clue->lat && $clue->lng)
                                <div class="mt-1.5">
                                    <span class="bg-primary/10 text-primary text-[9px] px-2 py-0.5 rounded-full">📍 {{ number_format($clue->lat, 4) }}, {{ number_format($clue->lng, 4) }}</span>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Actions --}}
                    @if($editingId !== $clue->id)
                        <div x-data="{ confirmDelete: false }" class="pl-1">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[10px] font-bold text-slate-400">
                                <span>{{ $clue->created_at->diffForHumans() }}</span>

                                @auth
                                    <button wire:click="toggleReply({{ $clue->id }})"
                                        class="hover:text-primary transition-colors">
                                        {{ $replyingTo === $clue->id ? 'Hủy trả lời' : 'Phản hồi' }}
                                    </button>

                                    @if(Auth::id() === $clue->user_id)
                                        <button wire:click="startEdit({{ $clue->id }})"
                                            class="hover:text-brand transition-colors">
                                            Chỉnh sửa
                                        </button>
                                        <button x-show="!confirmDelete" @click="confirmDelete = true"
                                            class="hover:text-red-500 transition-colors">
                                            Xóa
                                        </button>
                                    @endif
                                @endauth
                            </div>

                            {{-- Confirm xóa --}}
                            @auth
                                @if(Auth::id() === $clue->user_id)
                                    <div x-show="confirmDelete" x-transition
                                        class="mt-2 flex flex-wrap items-center gap-2 bg-red-50 border border-red-200 rounded-xl px-3 py-2">
                                        <span class="text-[10px] text-red-600 font-extrabold">Xóa manh mối này?</span>
                                        <div class="flex gap-2">
                                            <button wire:click="deleteClue({{ $clue->id }})" @click="confirmDelete = false"
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

                    {{-- Form reply inline --}}
                    @if($replyingTo === $clue->id)
                        <div class="flex gap-2 items-start ml-6 mt-2">
                            <div class="w-7 h-7 rounded-full bg-slate-200 flex-shrink-0 flex items-center justify-center text-[10px] font-bold text-slate-500">
                                {{ Auth::user()->initials ?? 'Me' }}
                            </div>
                            <div class="flex-1 space-y-2">
                                <textarea wire:model="replyBody" rows="2" placeholder="Viết phản hồi..."
                                    class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all resize-none
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
                                    <button wire:click="toggleReply({{ $clue->id }})"
                                        class="text-xs font-bold text-slate-500 px-4 py-2 rounded-xl hover:bg-slate-100 transition-colors">
                                        Hủy
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Replies --}}
                    @if($clue->replies->isNotEmpty())
                        <div class="ml-6 space-y-3 mt-2">
                            @foreach($clue->replies as $reply)
                                <div class="flex gap-2.5 items-start">
                                    <div class="w-7 h-7 rounded-full flex-shrink-0 flex items-center justify-center text-[10px] font-bold bg-slate-100 border border-slate-200 text-slate-500">
                                        {{ $reply->initials }}
                                    </div>
                                    <div class="flex-1 min-w-0">

                                        {{-- Edit reply --}}
                                        @if($editingId === $reply->id)
                                            <div class="space-y-2">
                                                <textarea wire:model="editBody" rows="2"
                                                    class="w-full px-3 py-2 border border-primary/40 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-primary bg-slate-50 focus:bg-white transition-all resize-none"></textarea>
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
                                            <div class="bg-slate-100 px-3 py-2.5 rounded-2xl inline-block max-w-full">
                                                <span class="text-xs font-extrabold text-slate-900">{{ $reply->display_name }}</span>
                                                <p class="text-xs text-slate-600 leading-relaxed font-medium break-words mt-0.5">{{ $reply->description }}</p>
                                            </div>
                                        @endif

                                        {{-- Reply actions --}}
                                        @if($editingId !== $reply->id)
                                            <div x-data="{ confirmDelete: false }" class="mt-1 pl-1">
                                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[10px] font-bold text-slate-400">
                                                    <span>{{ $reply->created_at->diffForHumans() }}</span>
                                                    @auth
                                                        @if(Auth::id() === $reply->user_id)
                                                            <button wire:click="startEdit({{ $reply->id }})"
                                                                class="hover:text-brand transition-colors">
                                                                Chỉnh sửa
                                                            </button>
                                                            <button x-show="!confirmDelete" @click="confirmDelete = true"
                                                                class="hover:text-red-500 transition-colors">
                                                                Xóa
                                                            </button>
                                                        @endif
                                                    @endauth
                                                </div>
                                                @auth
                                                    @if(Auth::id() === $reply->user_id)
                                                        <div x-show="confirmDelete" x-transition
                                                            class="mt-2 flex flex-wrap items-center gap-2 bg-red-50 border border-red-200 rounded-xl px-3 py-2">
                                                            <span class="text-[10px] text-red-600 font-extrabold">Xóa phản hồi này?</span>
                                                            <div class="flex gap-2">
                                                                <button wire:click="deleteClue({{ $reply->id }})" @click="confirmDelete = false"
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
        @empty
            <p class="text-xs text-slate-400 font-semibold text-center py-4">Chưa có manh mối nào. Hãy là người đầu tiên chia sẻ thông tin!</p>
        @endforelse
    </div>
</div>