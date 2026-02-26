@extends('layouts.app')

@section('title', '勤怠一覧')

@section('header_type', 'default')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">
@endpush

@section('content')
<div class="attlist">
  <div class="attlist__card">

    <h1 class="attlist__title">
      <img src="{{ asset('images/heading_bar.png') }}" alt="" class="heading-bar">
      勤怠一覧
    </h1>

    <div class="attlist-nav">
      <a href="{{ route('attendance.list', ['month' => $prevMonth]) }}" class="attlist-nav__link">
        <img src="{{ asset('images/arrow.png') }}" alt="" class="attlist-nav__arrow">
        <span>前月</span>
      </a>

      <div class="attlist-nav__center">
        <img
          src="{{ asset('images/calendar.png') }}"
          alt="カレンダー"
          class="attlist-nav__icon"
        >
        <span>{{ $currentMonthLabel }}</span>
      </div>

      <a href="{{ route('attendance.list', ['month' => $nextMonth]) }}" class="attlist-nav__link">
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
          @endphp

          <tr>
            <td>{{ $date->isoFormat('MM/DD(ddd)') }}</td>

            <td>
              {{ $attendance?->clock_in_at
                ? \Carbon\Carbon::createFromFormat('H:i:s', $attendance->clock_in_at)->format('H:i')
                : '' }}
            </td>

            <td>
              {{ $attendance?->clock_out_at
                ? \Carbon\Carbon::createFromFormat('H:i:s', $attendance->clock_out_at)->format('H:i')
                : '' }}
            </td>

            <td>{{ ($attendance?->break_label === '0:00') ? '' : ($attendance?->break_label ?? '') }}</td>
            <td>{{ ($attendance?->net_label === '0:00') ? '' : ($attendance?->net_label ?? '') }}</td>

            <td>
              <a class="attlist__detail"
                href="{{ route('attendance.detail', ['date' => $workDate]) }}">
                詳細
              </a>
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>

  </div>
</div>
@endsection
