@extends('layouts.app')
@section('title', 'ログイン画面')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('content')
<div class="auth">
    <h1 class="auth__title">ログイン</h1>

    <form method="POST" action="{{ route('login') }}" class="auth__form">
        @csrf

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

        <button type="submit" class="auth__button">ログインする</button>
    </form>

    <div class="auth__link">
        <a href="{{ route('register') }}">会員登録はこちら</a>
    </div>
</div>
@endsection