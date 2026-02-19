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

        <form method="POST" action="{{ route('admin.attendance.update', $attendance->id) }}">
            @csrf
            @method('PUT')

            @php
                $workDate = \Carbon\Carbon::parse($attendance->work_date)->toDateString();

                $in  = $attendance->clock_in_at;
                $out = $attendance->clock_out_at;

                $break1 = $attendance->breaks?->firstWhere('break_no', 1);
                $break2 = $attendance->breaks?->firstWhere('break_no', 2);

                $b1s = $break1?->break_start_at;
                $b1e = $break1?->break_end_at;
                $b2s = $break2?->break_start_at;
                $b2e = $break2?->break_end_at;

                $note = $attendance->note ?? '';
            @endphp

            <div class="attdetail__card">
                <table class="attdetail__table">
                    <tr>
                        <th>名前</th>
                        <td class="attdetail__value">{{ $attendance->user->name }}</td>
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
                                value="{{ old('clock_in_at', $in ? \Carbon\Carbon::parse($in)->format('H:i') : '') }}">
                            <span class="attdetail__tilde">〜</span>
                            <input type="time" name="clock_out_at" class="attdetail__time-input"
                                value="{{ old('clock_out_at', $out ? \Carbon\Carbon::parse($out)->format('H:i') : '') }}">
                            @error('clock_in_at')
                                <p class="attdetail__error">{{ $message }}</p>
                            @enderror
                            @error('clock_out_at')
                                <p class="attdetail__error">{{ $message }}</p>
                            @enderror
                        </td>
                    </tr>

                    <tr>
                        <th>休憩</th>
                        <td class="attdetail__time">
                            <input type="time" name="breaks[1][start]" class="attdetail__time-input"
                                value="{{ old('breaks.1.start', $b1s ? \Carbon\Carbon::parse($b1s)->format('H:i') : '') }}">
                            <span class="attdetail__tilde">〜</span>
                            <input type="time" name="breaks[1][end]" class="attdetail__time-input"
                                value="{{ old('breaks.1.end', $b1e ? \Carbon\Carbon::parse($b1e)->format('H:i') : '') }}">
                            @error('breaks.1.start') <p class="attdetail__error">{{ $message }}</p> @enderror
                            @error('breaks.1.end')   <p class="attdetail__error">{{ $message }}</p> @enderror
                        </td>
                    </tr>

                    <tr>
                        <th>休憩2</th>
                        <td class="attdetail__time">
                            <input type="time" name="breaks[2][start]" class="attdetail__time-input"
                                value="{{ old('breaks.2.start', $b2s ? \Carbon\Carbon::parse($b2s)->format('H:i') : '') }}">
                            <span class="attdetail__tilde">〜</span>
                            <input type="time" name="breaks[2][end]" class="attdetail__time-input"
                                value="{{ old('breaks.2.end', $b2e ? \Carbon\Carbon::parse($b2e)->format('H:i') : '') }}">
                            @error('breaks.2.start') <p class="attdetail__error">{{ $message }}</p> @enderror
                            @error('breaks.2.end')   <p class="attdetail__error">{{ $message }}</p> @enderror
                        </td>
                    </tr>

                    <tr>
                        <th>備考</th>
                        <td class="attdetail__memo">
                            <input type="text" name="note" class="attdetail__memo-input"
                                value="{{ old('note', $note) }}">
                            @error('note')
                                <p class="attdetail__error">{{ $message }}</p>
                            @enderror
                        </td>
                    </tr>
                </table>
            </div>

            <div class="attdetail__actions">
                <button type="submit" class="attdetail__btn">修正</button>
            </div>
        </form>
    </div>
</div>
@endsection
