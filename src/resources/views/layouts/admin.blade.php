<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '管理者画面')</title>

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    @php
        $headerType = trim($__env->yieldContent('header_type', 'default'));
    @endphp

    @include('partials.header-admin', ['type' => $headerType])

    <main class="l-main">
        @yield('content')
    </main>
</body>
</html>
