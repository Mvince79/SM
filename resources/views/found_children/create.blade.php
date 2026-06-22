@extends('layouts.app')

@section('title', 'Báo tin tìm được')

@section('content')
    <main class="max-w-3xl mx-auto px-4 py-12">
        @livewire('found-children.create-found-form')
    </main>
@endsection