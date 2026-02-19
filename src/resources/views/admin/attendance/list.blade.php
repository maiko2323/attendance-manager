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

            $in  = $attendance?->clock_in_at ? \Carbon\Carbon::createFromFormat('H:i:s', $attendance->clock_in_at)->format('H:i') : '';
            $out = $attendance?->clock_out_at ? \Carbon\Carbon::createFromFormat('H:i:s', $attendance->clock_out_at)->format('H:i') : '';

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
            if ($attendance && $attendance->clock_in_at && $attendance->clock_out_at) {
              $inC  = \Carbon\Carbon::createFromFormat('H:i:s', $attendance->clock_in_at);
              $outC = \Carbon\Carbon::createFromFormat('H:i:s', $attendance->clock_out_at);

              $workMinutes = $inC->diffInMinutes($outC);
              $netMinutes  = max(0, $workMinutes - $breakMinutes);

              $totalLabel  = sprintf('%d:%02d', intdiv($netMinutes, 60), $netMinutes % 60);
            }
          @endphp

          <tr>
            <td>{{ $user->name }}</td>
            <td>{{ $in }}</td>
            <td>{{ $out }}</td>
            <td>{{ $breakLabel }}</td>
            <td>{{ $totalLabel }}</td>
            <td>
              @if($attendance)
                <a class="attlist__detail"
                  href="{{ route('admin.attendance.detail', $attendance->id) }}">
                  詳細
                </a>
              @else
                <span class="attlist__detail is-disabled">詳細</span>
              @endif
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
