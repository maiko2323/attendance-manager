@extends('layouts.admin')

@section('title', '管理者ログイン')

@section('header_type', 'login')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('content')
    <div class="auth">
        <h1 class="auth__title">管理者ログイン</h1>

        <form method="POST" action="{{ route('login') }}" class="auth__form">
            @csrf
            <input type="hidden" name="login_type" value="admin">

            <div class="auth__group">
                <label for="email">メールアドレス</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}">
                @error('email')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="auth__group">
                <label for="password">パスワード</label>
                <input type="password" name="password" id="password">
                @error('password')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="auth__button">管理者ログインする</button>
        </form>
    </div>
@endsection
