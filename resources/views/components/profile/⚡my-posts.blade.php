<?php

use App\Models\MissingChild;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public ?int $deletingId = null;

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
    }

    public function cancelDelete(): void
    {
        $this->deletingId = null;
    }

    public function destroy(): void
    {
        if (!$this->deletingId) {
            return;
        }

        $post = MissingChild::where('id', $this->deletingId)->where('user_id', Auth::id())->first();

        if ($post) {
            $post->delete();
        }

        $this->deletingId = null;

        session()->flash('deleted', 'Đã xóa tin đăng.');
    }

    public function render()
    {
        $posts = MissingChild::where('user_id', Auth::id())->orderByDesc('created_at')->paginate(10);

        return view('components.profile.⚡my-posts', compact('posts'));
    }
};
?>

<div x-data="{
    showConfirm: false,
    init() {
        $watch('$wire.deletingId', val => { this.showConfirm = !!val; })
    }
}" class="space-y-4">

    {{-- Flash xóa thành công --}}
    @if (session('deleted'))
        <div
            class="bg-green-50 border border-green-200 text-green-700 text-xs font-bold px-4 py-3 rounded-2xl flex items-center gap-2">
            ✅ {{ session('deleted') }}
        </div>
    @endif

    {{-- ── Danh sách tin ── --}}
    @forelse($posts as $post)
        <div class="bg-white border border-slate-200/80 rounded-3xl p-5 shadow-premium
                        flex flex-col sm:flex-row gap-5 items-center justify-between">

            {{-- Ảnh + thông tin --}}
            <div class="flex gap-4 items-center w-full sm:w-auto min-w-0">
                <div class="w-16 h-16 rounded-2xl overflow-hidden bg-slate-100 border border-slate-200 flex-shrink-0">
                    <img src="{{ $post->thumb_url }}" alt="{{ $post->full_name }}" class="w-full h-full object-cover" />
                </div>

                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="font-extrabold text-slate-900 text-sm truncate">
                            {{ $post->full_name }}
                        </h3>

                        {{-- Chip màu trạng thái --}}
                        @php
                            $chipClass = match ($post->status) {
                                'PENDING' => 'bg-amber-100 text-amber-600 border-amber-200',
                                'APPROVED' => 'bg-success-light text-success border-success/35',
                                'REJECTED' => 'bg-red-100 text-red-500 border-red-200',
                                'FOUND' => 'bg-violet-100 text-violet-600 border-violet-200',
                                default => 'bg-slate-100 text-slate-500 border-slate-200',
                            };
                            $chipLabel = match ($post->status) {
                                'PENDING' => 'Chờ duyệt',
                                'APPROVED' => 'Đã duyệt',
                                'REJECTED' => 'Từ chối',
                                'FOUND' => 'Đã tìm thấy',
                                default => $post->status,
                            };
                        @endphp
                        <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full border {{ $chipClass }}">
                            {{ $chipLabel }}
                        </span>

                        <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full border flex items-center gap-1
                 {{ $post->review_level === 'ENHANCED'
            ? 'bg-orange-100 text-orange-600 border-orange-200'
            : 'bg-brand/10 text-brand border-brand/20' }}">
                            @if ($post->review_level === 'ENHANCED')
                                <x-heroicon-o-shield-exclamation class="w-3 h-3" /> Kiểm duyệt kỹ
                            @else
                                <x-heroicon-o-shield-check class="w-3 h-3" /> Chuẩn
                            @endif
                        </span>
                    </div>

                    <p class="text-[10px] text-slate-400 font-bold mt-1 uppercase tracking-wide">
                        <x-heroicon-o-map-pin class="w-3.5 h-3.5 inline" />
                        {{ $post->district ?? ($post->province?->name ?? '—') }}
                        · Mất tích: {{ $post->missing_date?->format('d/m/Y') ?? '—' }}
                    </p>
                    <p class="text-[10px] text-slate-300 font-semibold mt-0.5">
                        Đăng: {{ $post->created_at->format('d/m/Y H:i') }}
                    </p>
                </div>
            </div>

            {{-- Nút hành động --}}
            <div class="flex gap-2 w-full sm:w-auto justify-end flex-shrink-0">
                {{-- Sửa — route missing.edit sẽ được thêm ở task liên quan --}}
                <a href="{{ url('/missing/edit/' . $post->id) }}" class="border border-slate-200 text-slate-700 text-xs font-extrabold py-2.5 px-4
                              rounded-xl hover:bg-slate-50 transition-colors">
                    ✏️ Sửa
                </a>

                {{-- Xóa — mở confirm modal qua Livewire --}}
                <button type="button" wire:click="confirmDelete({{ $post->id }})" class="border border-red-200 text-red-500 text-xs font-extrabold py-2.5 px-4
                                   rounded-xl hover:bg-red-50 transition-colors">
                    🗑 Xóa
                </button>
            </div>
        </div>
    @empty
        {{-- ── Empty state ── --}}
        <div class="text-center py-16 space-y-4">
            <div class="text-5xl">📭</div>
            <h3 class="font-extrabold text-slate-700 text-base">Bạn chưa đăng tin nào.</h3>
            <a href="{{ route('missing.create') }}" class="inline-block bg-primary text-white font-bold text-sm px-6 py-3
                          rounded-xl hover:bg-primary-dark transition-colors shadow-md shadow-primary/10">
                Đăng tin ngay →
            </a>
        </div>
    @endforelse

    {{-- Pagination --}}
    @if ($posts->hasPages())
        <div class="pt-2">
            {{ $posts->links() }}
        </div>
    @endif

    {{-- ── Confirm Modal Xóa (Alpine x-show) ── --}}
    <div x-show="showConfirm" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4"
        style="display: none;">
        <div @click.away="$wire.cancelDelete()"
            class="bg-white border border-slate-200 rounded-3xl max-w-sm w-full p-6 space-y-5 shadow-2xl">
            <div class="text-center space-y-2">
                <div class="text-4xl">🗑️</div>
                <h3 class="font-black text-slate-900 text-base">Xác nhận xóa tin?</h3>
                <p class="text-xs text-slate-500 font-semibold leading-relaxed">
                    Tin đăng này sẽ bị xóa và không thể khôi phục. Bạn có chắc chắn không?
                </p>
            </div>

            <div class="flex gap-3 justify-center pt-1">
                <button type="button" wire:click="cancelDelete" class="text-xs font-bold text-slate-600 px-5 py-2.5 border border-slate-200
                               rounded-xl hover:bg-slate-50 transition-colors">
                    Không, giữ lại
                </button>
                <button type="button" wire:click="destroy" wire:loading.attr="disabled" class="bg-red-500 text-white text-xs font-extrabold px-5 py-2.5
                               rounded-xl hover:bg-red-600 transition-colors disabled:opacity-60">
                    <span wire:loading.remove wire:target="destroy">Xóa tin</span>
                    <span wire:loading wire:target="destroy">⏳ Đang xóa...</span>
                </button>
            </div>
        </div>
    </div>

</div>