@extends('layouts.app')
@section('title', ' Báo tin khẩn cấp')

@section('content')
    <main class="max-w-3xl mx-auto px-4 py-12">
        @livewire('missing.create-missing-form')
    </main>
@endsection
