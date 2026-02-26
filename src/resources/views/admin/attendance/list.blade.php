@extends('layouts.admin')

@section('title', '勤怠一覧_管理者')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">
@endpush

@section('content')
<div class="attlist">

  <div class="attlist__card">

    <h1 class="attlist__title">
      <img src="{{ asset('images/heading_bar.png') }}" alt="" class="heading-bar">
      {{ $titleLabel }}
    </h1>

    <div class="attlist-nav">
      {{-- 前日 --}}
      <a href="{{ route('admin.attendance.list', ['date' => $prevDate]) }}" class="attlist-nav__link">
        <img src="{{ asset('images/arrow.png') }}" alt="" class="attlist-nav__arrow">
        <span>前日</span>
      </a>

      {{-- 中央（日付） --}}
      <div class="attlist-nav__center">
        <img src="{{ asset('images/calendar.png') }}" alt="カレンダー" class="attlist-nav__icon">
        <span>{{ $dateLabel }}</span>
      </div>

      {{-- 翌日 --}}
      <a href="{{ route('admin.attendance.list', ['date' => $nextDate]) }}" class="attlist-nav__link">
        <span>翌日</span>
        <img src="{{ asset('images/arrow.png') }}" alt="" class="attlist-nav__arrow is-left">
      </a>
    </div>

    <div class="attlist__tablewrap">
      <table class="attlist__table">
        <thead>
          <tr>
            <th>名前</th>
            <th>出勤</th>
            <th>退勤</th>
            <th>休憩</th>
            <th>合計</th>
            <th>詳細</th>
          </tr>
        </thead>

        <tbody>
        @forelse($users as $user)
          @php
            $attendance = $user->attendances->firstWhere('work_date', $date);

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
            <td>{{ $user->name }}</td>
            <td>{{ $in }}</td>
            <td>{{ $out }}</td>
            <td>{{ $breakLabel === '0:00' ? '' : $breakLabel }}</td>
            <td>{{ $totalLabel }}</td>
            <td>
              <a class="attlist__detail"
                href="{{ route('admin.attendance.detail', ['user' => $user->id, 'date' => $date]) }}">
                詳細
              </a>
            </td>
          </tr>
        @empty
          <tr><td colspan="6">スタッフがいません。</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>
@endsection
