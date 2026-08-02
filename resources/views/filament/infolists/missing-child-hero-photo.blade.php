@php
    $state = $getState() ?? [];

    $photoUrl = $state['url'] ?? null;
    $otherCount = (int) ($state['other_count'] ?? 0);
@endphp

<div style="
        position: relative;
        width: 100%;
        height: 350px;
        overflow: hidden;
        border-radius: 12px;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
    ">
    
    @if ($photoUrl)
        <img src="{{ $photoUrl }}" alt="Ảnh trẻ mất tích" style="
                    display: block;
                    width: 100%;
                    height: 350px;
                    object-fit: cover;
                    border-radius: 12px;
                ">
        @if ($otherCount > 0)
            <button type="button" onclick="
                    const photoTab = [...document.querySelectorAll('[role=tab], button')]
                        .find((element) => element.textContent.trim() === 'Ảnh');

                    if (!photoTab) {
                        return;
                    }

                    photoTab.click();

                    window.setTimeout(() => {
                        photoTab.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }, 150);
                " style="
                    position: absolute;
                    right: 16px;
                    bottom: 16px;
                    z-index: 10;
                    padding: 8px 14px;
                    border: 0;
                    border-radius: 9999px;
                    background: rgba(17, 24, 39, 0.78);
                    color: #ffffff;
                    font-size: 14px;
                    font-weight: 600;
                    line-height: 20px;
                    cursor: pointer;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.18);
                    backdrop-filter: blur(6px);
                ">
                +{{ $otherCount }} ảnh khác
            </button>
        @endif
    @else
        <div style="
                    display: flex;
                    width: 100%;
                    height: 350px;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    gap: 10px;
                    border-radius: 12px;
                    background: #f3f4f6;
                    color: #6b7280;
                ">
            <x-heroicon-o-photo style="
                        display: block;
                        width: 42px;
                        height: 42px;
                    " />

            <span style="
                        font-size: 15px;
                        font-weight: 600;
                    ">
                Chưa có ảnh
            </span>
        </div>
    @endif
</div>