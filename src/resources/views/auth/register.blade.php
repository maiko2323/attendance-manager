@extends('layouts.app')

@section('title', '会員登録画面')

@section('header_type', 'guest')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('content')
    <div class="auth">
        <h1 class="auth__title">会員登録</h1>

        <form method="POST" action="{{ route('register') }}" class="auth__form">
            @csrf

            <div class="auth__group">
                <label for="name">名前</label>
                <input type="text" name="name" value="{{ old('name') }}">
                @error('name')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="auth__group">
                <label for="email">メールアドレス</label>
                <input type="text" name="email" value="{{ old('email') }}">
                @error('email')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="auth__group">
                <label for="password">パスワード</label>
                <input type="password" name="password">
                @error('password')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="auth__group">
                <label for="password_confirmation">パスワード確認</label>
                <input type="password" name="password_confirmation" id="password_confirmation">
                @error('password_confirmation')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="auth__button">登録する</button>
        </form>

        <div class="auth__link">
            <a href="{{ route('login') }}">ログインはこちら</a>
        </div>
    </div>
@endsection
