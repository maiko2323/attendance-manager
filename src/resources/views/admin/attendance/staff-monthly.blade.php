@extends('layouts.admin')

@section('title', '月次勤怠')

@section('header_type', 'admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">
@endpush

@section('content')
<div class="attlist">
    <div class="attlist__card">

        <h1 class="attlist__title">
            <img src="{{ asset('images/heading_bar.png') }}" alt="" class="heading-bar">
            {{ $user->name }}さんの勤怠
        </h1>

        <div class="attlist-nav">
            <a href="{{ route('admin.attendance.staff', ['user' => $user->id, 'month' => $prevMonth]) }}"
                class="attlist-nav__link">
                <img src="{{ asset('images/arrow.png') }}" alt="" class="attlist-nav__arrow">
                <span>前月</span>
            </a>

            <div class="attlist-nav__center">
                <img src="{{ asset('images/calendar.png') }}" alt="カレンダー" class="attlist-nav__icon">
                <span>{{ $currentMonthLabel }}</span>
            </div>

            <a href="{{ route('admin.attendance.staff', ['user' => $user->id, 'month' => $nextMonth]) }}"
                class="attlist-nav__link">
                <span>翌月</span>
                <img src="{{ asset('images/arrow.png') }}" alt="" class="attlist-nav__arrow is-left">
            </a>

        </div>

        <div class="attlist__tablewrap">
            <table class="attlist__table">
                <thead>
                    <tr>
                        <th>日付</th>
                        <th>出勤</th>
                        <th>退勤</th>
                        <th>休憩</th>
                        <th>合計</th>
                        <th>詳細</th>
                    </tr>
                </thead>

                <tbody>
                @foreach($dates as $date)
                    @php
                        $workDate = $date->toDateString();
                        $attendance = $attendances->get($workDate);

                        $in  = $attendance?->clock_in_at
                            ? \Carbon\Carbon::createFromFormat('H:i:s', $attendance->clock_in_at)->format('H:i')
                            : '';

                        $out = $attendance?->clock_out_at
                            ? \Carbon\Carbon::createFromFormat('H:i:s', $attendance->clock_out_at)->format('H:i')
                            : '';

                        $breakLabel = $attendance ? $attendance->break_label : '';

                        $totalLabel = ($attendance && $attendance->clock_in_at && $attendance->clock_out_at)
                            ? $attendance->net_label
                            : '';
                    @endphp

                    <tr>
                        <td>{{ $date->isoFormat('MM/DD(ddd)') }}</td>
                        <td>{{ $in }}</td>
                        <td>{{ $out }}</td>
                        <td>{{ $breakLabel === '0:00' ? '' : $breakLabel }}</td>
                        <td>{{ $totalLabel === '0:00' ? '' : $totalLabel }}</td>
                        <td>
                            <a class="attlist__detail"
                                href="{{ route('admin.attendance.detail', ['user' => $user->id, 'date' => $workDate]) }}">
                                詳細
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="attlist__actions">
            <a href="{{ route('admin.attendance.staff.csv', [
                'user' => $user->id,
                'month' => request('month', now()->format('Y-m'))
                ]) }}"
                class="attlist__btn">
                CSV出力
            </a>
        </div>

    </div>
</div>
@endsection
