@extends('layouts.admin')

@section('title', 'スタッフ一覧')
@section('header_type', 'admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/staff-list.css') }}">
@endpush

@section('content')
<div class="stafflist">
    <div class="stafflist__card">

        <h1 class="stafflist__title">
            <img src="{{ asset('images/heading_bar.png') }}" alt="" class="heading-bar">
            スタッフ一覧
        </h1>

        <div class="stafflist__tablewrap">
            <table class="stafflist__table">
                <thead>
                    <tr>
                        <th>名前</th>
                        <th>メールアドレス</th>
                        <th>月次勤怠</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <a class="stafflist__detail" href="{{ route('admin.attendance.staff', ['user' => $user->id]) }}">
                                詳細
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3">スタッフがいません。</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
