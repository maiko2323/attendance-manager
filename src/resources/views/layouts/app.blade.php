<!doctype html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '勤怠管理アプリ')</title>

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>

<body>
    @php
        $headerType = trim($__env->yieldContent('header_type')) ?: 'default';
    @endphp

    @if ($headerType === 'admin')
        @include('partials.header-admin', ['type' => $headerType])
    @else
        @include('partials.header', ['type' => $headerType])
    @endif

    <main class="l-main">
        @yield('content')
    </main>

    @stack('scripts')
</body>

</html>
