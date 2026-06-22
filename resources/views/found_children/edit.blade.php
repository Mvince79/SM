@extends('layouts.app')

@section('title', 'Sửa hồ sơ tìm được')

@section('content')
    <main class="max-w-3xl mx-auto px-4 py-12">
        @livewire('found-children.edit-found-form', ['foundChild' => $foundChild])
    </main>
@endsection