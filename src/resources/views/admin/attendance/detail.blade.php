@extends('layouts.admin')

@section('title', '勤怠詳細画面_管理者')

@section('header_type', 'admin')

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

        <form method="POST"action="{{ route('admin.attendance.upsert', ['user' => $user->id, 'date' => $targetDate]) }}">
            @csrf

            @php
                $workDate = $targetDate;

                $in  = $attendance?->clock_in_at;
                $out = $attendance?->clock_out_at;

                $break1 = $attendance?->breaks?->firstWhere('break_no', 1);
                $break2 = $attendance?->breaks?->firstWhere('break_no', 2);

                $b1s = $break1?->break_start_at;
                $b1e = $break1?->break_end_at;
                $b2s = $break2?->break_start_at;
                $b2e = $break2?->break_end_at;

                $note = $attendance?->note ?? '';
            @endphp

            <div class="attdetail__card">
                <table class="attdetail__table">
                    <tr>
                        <th>名前</th>
                        <td class="attdetail__value">{{ $user->name }}</td>
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
                            <input type="time" name="clock_in_at" class="attdetail__time-input"
                                value="{{ old('clock_in_at', $in ? \Carbon\Carbon::parse($in)->format('H:i') : '') }}" @disabled($isPending)>
                            <span class="attdetail__tilde">〜</span>
                            <input type="time" name="clock_out_at" class="attdetail__time-input"
                                value="{{ old('clock_out_at', $out ? \Carbon\Carbon::parse($out)->format('H:i') : '') }}" @disabled($isPending)>
                            @error('clock_in_at')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                            @error('clock_out_at')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </td>
                    </tr>

                    <tr>
                        <th>休憩</th>
                        <td class="attdetail__time">
                            <input type="time" name="breaks[1][start]" class="attdetail__time-input"
                                value="{{ old('breaks.1.start', $b1s ? \Carbon\Carbon::parse($b1s)->format('H:i') : '') }}" @disabled($isPending)>
                            <span class="attdetail__tilde">〜</span>
                            <input type="time" name="breaks[1][end]" class="attdetail__time-input"
                                value="{{ old('breaks.1.end', $b1e ? \Carbon\Carbon::parse($b1e)->format('H:i') : '') }}" @disabled($isPending)>
                            @error('breaks.1.start')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                            @error('breaks.1.end')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </td>
                    </tr>

                    <tr>
                        <th>休憩2</th>
                        <td class="attdetail__time">
                            <input type="time" name="breaks[2][start]" class="attdetail__time-input"
                                value="{{ old('breaks.2.start', $b2s ? \Carbon\Carbon::parse($b2s)->format('H:i') : '') }}" @disabled($isPending)>
                            <span class="attdetail__tilde">〜</span>
                            <input type="time" name="breaks[2][end]" class="attdetail__time-input"
                                value="{{ old('breaks.2.end', $b2e ? \Carbon\Carbon::parse($b2e)->format('H:i') : '') }}" @disabled($isPending) >
                            @error('breaks.2.start')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                            @error('breaks.2.end')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </td>
                    </tr>

                    <tr>
                        <th>備考</th>
                        <td class="attdetail__memo">
                            <input type="text" name="reason" class="attdetail__memo-input"
                                value="{{ old('reason', $attendance->reason ?? '') }}" @disabled($isPending)>
                            @error('reason')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </td>
                    </tr>
                </table>
            </div>

            @if(!$isPending)
                <div class="attdetail__actions">
                    <button type="submit" class="attdetail__btn">修正</button>
                </div>
            @endif
        </form>

        @if($isPending)
            <p class="attdetail__notice">*承認待ちのため修正はできません。</p>
        @endif

        @if(session('message'))
            <p class="attdetail__notice">{{ session('message') }}</p>
        @endif
    </div>
</div>
@endsection
