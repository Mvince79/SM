@extends('layouts.app')
@section('content')
<main class="max-w-4xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-black mb-6">Hồ sơ cần xác nhận tiếp nhận</h1>
    @livewire('verifier.verifier-dashboard')
</main>
@endsection