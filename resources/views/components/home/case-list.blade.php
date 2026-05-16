@props(['cases' => collect()])

<div class="section">
    <div class="section-header">
        <div class="section-title">Ca đang tìm kiếm</div>
        <a href="#" class="section-link">Xem tất cả</a>
    </div>

    @if($cases->isEmpty())
        <x-ui.empty-state message="Chưa có ca nào được đăng."
            hint="Nhấn &quot;Báo trẻ mất tích&quot; để đăng ca đầu tiên." />
    @else
        @foreach($cases as $case)
            {{-- Sprint 1: <x-home.case-card :case="$case" /> --}}
        @endforeach
    @endif
</div>