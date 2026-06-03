@extends('layouts.app')

@section('content')
    <div class="h-[calc(100vh-120px)] min-h-[620px] bg-white">
        @livewire('chat.chat-container')
    </div>
@endsection
