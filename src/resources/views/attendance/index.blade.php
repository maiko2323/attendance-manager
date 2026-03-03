@extends('layouts.app')

@section('title', '勤怠登録')

@if ($state === 'after')
    @section('header_type', 'after')
@endif

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/index.css') }}">
@endpush

@section('content')
    <div class="att">
        <div class="att__inner">

            <div class="att__badge">
                @if ($state === 'before')
                    勤務外
                @elseif ($state === 'working')
                    出勤中
                @elseif ($state === 'breaking')
                    休憩中
                @elseif ($state === 'after')
                    退勤済
                @endif
            </div>

            <div class="att__date" id="todayDate">
                {{ $now->format('Y年n月j日') }}({{ ['日', '月', '火', '水', '木', '金', '土'][$now->dayOfWeek] }})
            </div>

            <div class="att__time" id="currentTime">
                {{ $now->format('H:i') }}
            </div>

            @if ($state === 'before')
                <div class="att__actions">
                    <form method="POST" action="{{ route('attendance.start') }}">
                        @csrf
                        <button class="btn btn-black">出勤</button>
                    </form>
                </div>

            @elseif ($state === 'working')
                <div class="att__actions att__actions--row">
                    <form method="POST" action="{{ route('attendance.end') }}">
                        @csrf
                        <button type="submit" class="btn btn-black">退勤</button>
                    </form>
                    <form method="POST" action="{{ route('attendance.break.start') }}">
                        @csrf
                        <button type="submit" class="btn btn-white">休憩入</button>
                    </form>
                </div>

            @elseif ($state === 'breaking')
                <div class="att__actions">
                    <form method="POST" action="{{ route('attendance.break.end') }}">
                        @csrf
                        <button type="submit" class="btn btn-white">休憩戻</button>
                    </form>
                </div>

            @elseif ($state === 'after')
                <div class="att__message">お疲れ様でした。</div>
            @endif

        </div>
    </div>

@endsection
