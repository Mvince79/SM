@extends('layouts.app')

@section('title', 'Trang chủ')

@section('content')
    <x-home.greeting :user="auth()->user()" />
    <x-home.quick-actions />
    <x-home.info-banner />
    <x-home.case-list />
@endsection