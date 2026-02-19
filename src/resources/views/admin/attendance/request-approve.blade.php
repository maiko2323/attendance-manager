@extends('layouts.admin')

@section('title', '修正申請承認画面')

@section('header_type', 'default')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/detail.css') }}">
@endpush

@section('content')
<div class="attdetail">
<div class="attdetail__wrap">

    <h1 class="attdetail__title">
        <img src="{{ asset('images/heading_bar.png') }}" alt="" class="heading-bar">
        勤怠詳細
    </h1>

    @php
        // 申請の出退勤（承認対象）
        $in  = $req->request_clock_in_at;
        $out = $req->request_clock_out_at;

        // 休憩は勤怠側（申請に休憩カラムが無い想定）
        $break1 = $req->attendance?->breaks?->firstWhere('break_no', 1);
        $break2 = $req->attendance?->breaks?->firstWhere('break_no', 2);

        $b1s = $break1?->break_start_at;
        $b1e = $break1?->break_end_at;
        $b2s = $break2?->break_start_at;
        $b2e = $break2?->break_end_at;

        $reason = $req->reason;

        $workDate = $req->attendance?->work_date;
        $isApproved = $req->status === 'approved';
    @endphp

    <div class="attdetail__card">
        <table class="attdetail__table">
            <tr>
                <th>名前</th>
                <td class="attdetail__value">{{ $req->user->name }}</td>
            </tr>

            <tr>
                <th>日付</th>
                <td colspan="2" class="attdetail__value">
                    <span class="attdetail__date-item">{{ \Carbon\Carbon::parse($workDate)->isoFormat('YYYY年') }}</span>
                    <span class="attdetail__date-item">{{ \Carbon\Carbon::parse($workDate)->isoFormat('M月D日') }}</span>
                </td>
            </tr>

            <tr>
                <th>出勤・退勤</th>
                <td class="attdetail__time">
                    <span class="attdetail__time-value">{{ $in ? \Carbon\Carbon::parse($in)->format('H:i') : '' }}</span>
                    <span class="attdetail__tilde">〜</span>
                    <span class="attdetail__time-value">{{ $out ? \Carbon\Carbon::parse($out)->format('H:i') : '' }}</span>
                </td>
            </tr>

            <tr>
                <th>休憩</th>
                <td class="attdetail__time">
                    <span class="attdetail__time-value">{{ $b1s ? \Carbon\Carbon::parse($b1s)->format('H:i') : '' }}</span>
                    <span class="attdetail__tilde">〜</span>
                    <span class="attdetail__time-value">{{ $b1e ? \Carbon\Carbon::parse($b1e)->format('H:i') : '' }}</span>
                </td>
            </tr>

            <tr>
                <th>休憩2</th>
                <td class="attdetail__time">
                    <span class="attdetail__time-value">{{ $b2s ? \Carbon\Carbon::parse($b2s)->format('H:i') : '' }}</span>
                    <span class="attdetail__tilde">〜</span>
                    <span class="attdetail__time-value">{{ $b2e ? \Carbon\Carbon::parse($b2e)->format('H:i') : '' }}</span>
                </td>
            </tr>

            <tr>
                <th>備考</th>
                <td class="attdetail__memo">
                    <span class="attdetail__memo-text">{{ $reason }}</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="attdetail__actions">
        @if($isApproved)
            <button type="button" class="attdetail__btn" disabled style="background:#777; cursor:not-allowed;">
            承認済み
            </button>
        @else
            <form method="POST" action="{{ route('admin.stamp_correction_request.approve', $req->id) }}">
            @csrf
            <button type="submit" class="attdetail__btn">承認</button>
            </form>
        @endif
    </div>

</div>
</div>
@endsection
