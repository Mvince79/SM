@props(['user'])

<nav class="topnav">
    <div class="nav-brand">
        <div class="nav-brand-mark">
            <svg viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="7" />
                <path d="m21 21-4.35-4.35" />
            </svg>
        </div>
        <div>
            <div class="nav-brand-name">Tìm Kiếm Trẻ Em</div>
            <div class="nav-brand-sub">Cùng nhau tìm về nhà</div>
        </div>
    </div>

    <div class="nav-right">
        <div class="nav-avatar" title="{{ $user->full_name ?? '' }}">
            @if($user?->avatar_url)
                <img src="{{ $user->avatar_url }}" alt="{{ $user->full_name }}">
            @else
                {{ strtoupper(mb_substr($user->full_name ?? 'U', 0, 1)) }}
            @endif
        </div>
        <form method="POST" action="{{ route('auth.logout') }}">
            @csrf
            <button type="submit" class="nav-logout">Đăng xuất</button>
        </form>
    </div>
</nav>