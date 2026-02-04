@extends('layouts.app')
@section('title', '勤怠登録')

{{-- 退勤後ヘッダー切替 --}}
@if($state === 'after')
  @section('header_type', 'after')
@endif

@push('styles')
<link rel="stylesheet" href="{{ asset('css/index.css') }}">
@endpush

@section('content')
<div class="att">
  <div class="att__inner">

    {{-- 状態バッジ --}}
    <div class="att__badge">
      @if ($state === 'before') 勤務外
      @elseif ($state === 'working') 出勤中
      @elseif ($state === 'breaking') 休憩中
      @elseif ($state === 'after') 退勤済
      @endif
    </div>

    {{-- 日付 --}}
    <div class="att__date" id="todayDate">----</div>

    {{-- 時刻 --}}
    <div class="att__time" id="currentTime">--:--</div>

    {{-- 出勤前 --}}
    @if ($state === 'before')
      <form method="POST" action="{{ route('attendance.start') }}">
        @csrf
      <button type="submit" class="btn btn-black">出勤</button>
      </form>

    {{-- 出勤後 --}}
    @elseif ($state === 'working')
      <div class="att__actions att__actions--row">
        <button class="btn btn-black">退勤</button>
        <button class="btn btn-white">休憩入</button>
      </div>

    {{-- 休憩中 --}}
    @elseif ($state === 'breaking')
      <div class="att__actions">
        <button class="btn btn-white">休憩戻</button>
      </div>

    {{-- 退勤後 --}}
    @elseif ($state === 'after')
      <div class="att__message">お疲れ様でした。</div>
    @endif

  </div>
</div>

@push('scripts')
<script>
  function pad(n){ return String(n).padStart(2,'0'); }
  function jpDow(d){ return ['日','月','火','水','木','金','土'][d]; }

  function render(){
    const now = new Date();
    const y = now.getFullYear();
    const m = now.getMonth()+1;
    const d = now.getDate();
    const dow = jpDow(now.getDay());

    const hh = pad(now.getHours());
    const mm = pad(now.getMinutes());

    document.getElementById('todayDate').textContent = `${y}年${m}月${d}日(${dow})`;
    document.getElementById('currentTime').textContent = `${hh}:${mm}`;
  }
  render();
  setInterval(render, 1000);
</script>
@endpush
@endsection
