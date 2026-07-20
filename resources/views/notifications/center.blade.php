@extends('layouts.app')
@section('content')
<main class="max-w-2xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-black mb-6">Trung tâm thông báo</h1>
    @livewire('notifications.notification-center')
</main>
@endsection