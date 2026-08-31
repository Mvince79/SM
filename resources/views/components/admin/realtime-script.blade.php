@props([])

{{-- Realtime badge cho admin: khởi tạo authUserId (Filament không set sẵn) rồi nạp
     module admin-realtime.js đã compile bởi Vite. Khi Reverb chưa cấu hình, module
     tự bỏ qua (graceful) nên không ảnh hưởng trang admin. --}}
<script>
    window.authUserId = window.authUserId ?? {{ auth()->id() ?? 'null' }};
</script>

@vite('resources/js/admin-realtime.js')
