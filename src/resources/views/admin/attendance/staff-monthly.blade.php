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

                        $in  = $attendance?->clock_in_at;
                        $out = $attendance?->clock_out_at;

                        $breakMinutes = 0;
                        if ($attendance) {
                            foreach ($attendance->breaks ?? [] as $b) {
                                if ($b->break_start_at && $b->break_end_at) {
                                    $s = \Carbon\Carbon::createFromFormat('H:i:s', $b->break_start_at);
                                    $e = \Carbon\Carbon::createFromFormat('H:i:s', $b->break_end_at);
                                    $breakMinutes += $s->diffInMinutes($e);
                                }
                            }
                        }
                        $breakLabel = $attendance ? sprintf('%d:%02d', intdiv($breakMinutes, 60), $breakMinutes % 60) : '';

                        $totalLabel = '';
                        if ($attendance && $in && $out) {
                            $inC  = \Carbon\Carbon::createFromFormat('H:i:s', $in);
                            $outC = \Carbon\Carbon::createFromFormat('H:i:s', $out);
                            $workMinutes = $inC->diffInMinutes($outC);
                            $netMinutes  = max(0, $workMinutes - $breakMinutes);
                            $totalLabel  = sprintf('%d:%02d', intdiv($netMinutes, 60), $netMinutes % 60);
                        }
                    @endphp

                    <tr>
                        <td>{{ $date->isoFormat('MM/DD(ddd)') }}</td>
                        <td>{{ $in ? \Carbon\Carbon::createFromFormat('H:i:s', $in)->format('H:i') : '' }}</td>
                        <td>{{ $out ? \Carbon\Carbon::createFromFormat('H:i:s', $out)->format('H:i') : '' }}</td>
                        <td>{{ $breakLabel }}</td>
                        <td>{{ $totalLabel }}</td>
                        <td>
                            @if($attendance)
                                <a class="attlist__detail" href="{{ route('admin.attendance.detail', $attendance->id) }}">詳細</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="attlist__actions">
            <button class="attlist__btn">CSV出力</button>
        </div>

    </div>
</div>
@endsection
