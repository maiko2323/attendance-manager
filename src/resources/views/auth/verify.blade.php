@extends('layouts.app')

@section('title', 'メール認証誘導画面')

@section('header_type', 'guest')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/verify.css') }}">
@endpush

@section('content')
<div class="verify">

    <p class="verify__text">
        登録していただいたメールアドレスに認証メールを送付しました。<br>
        メール認証を完了してください。
    </p>

    {{-- Mailhog確認用 --}}
    <a href="http://localhost:8025"target="_blank"class="verify__button">
        認証はこちらから
    </a>

    {{-- 再送 --}}
    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="verify__link">
            認証メールを再送する
        </button>
    </form>

    @if (session('message'))
        <p class="verify__notice">{{ session('message') }}</p>
    @endif

</div>
@endsection