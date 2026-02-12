@extends('layouts.app')

@section('title', '申請一覧')

@section('header_type', 'default')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/requestlist.css') }}">
@endpush

@section('content')
<div class="requestlist">
    <div class="requestlist__wrap">

        <h1 class="requestlist__title">
            <img src="{{ asset('images/heading_bar.png') }}" alt="" class="heading-bar">
            申請一覧
        </h1>

        <div class="requestlist__tabs">
            <a href="{{ route('stamp_correction_request.list', ['tab' => 'pending']) }}"
                class="requestlist__tab {{ $tab === 'pending' ? 'is-active' : '' }}">
                承認待ち
            </a>

            <a href="{{ route('stamp_correction_request.list', ['tab' => 'approved']) }}"
                class="requestlist__tab {{ $tab === 'approved' ? 'is-active' : '' }}">
                承認済み
            </a>
        </div>

        <div class="requestlist__divider"></div>

        <div class="requestlist__card">
            <table class="requestlist__table">
                <thead>
                    <tr>
                        <th>状態</th>
                        <th>名前</th>
                        <th>対象日時</th>
                        <th>申請理由</th>
                        <th>申請日時</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                        <tr>
                            <td>{{ $req->status === 'pending' ? '承認待ち' : '承認済み' }}</td>
                            <td>{{ Auth::user()->name }}</td>

                            <td>
                                {{ $req->attendance?->work_date
                                    ? \Carbon\Carbon::parse($req->attendance->work_date)->format('Y/m/d')
                                    : '-' }}
                            </td>

                            <td>{{ $req->reason }}</td>

                            <td>{{ \Carbon\Carbon::parse($req->created_at)->format('Y/m/d') }}</td>

                            <td>
                                <a class="requestlist__detail"
                                    href="{{ route('attendance.detail', ['date' => $req->attendance?->work_date]) }}">
                                    詳細
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="requestlist__empty">申請はありません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</div>
@endsection

