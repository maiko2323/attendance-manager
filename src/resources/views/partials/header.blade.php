<header class="g-header">
    <div class="g-header__inner">
        <a class="g-header__brand" href="{{ url('/attendance') }}">
            <img class="g-header__logo" src="{{ asset('images/logo.svg') }}" alt="COACHTECH">
        </a>

        {{-- 会員登録/ログイン：ロゴのみ --}}
        @if ($type === 'guest')
            <div class="g-header__spacer"></div>

            {{-- 退勤後：今月の出勤一覧 / 申請一覧 / ログアウト --}}
        @elseif ($type === 'after')
            <nav class="g-header__nav">
                <a href="{{ url('/attendance/list') }}">今月の出勤一覧</a>
                <a href="{{ url('/stamp_correction_request/list') }}">申請一覧</a>
                <form method="POST" action="{{ url('/logout') }}" class="g-header__logout-form">
                    @csrf
                    <button type="submit" class="g-header__logout">ログアウト</button>
                </form>
            </nav>

            {{-- その他：勤怠 / 勤怠一覧 / 申請 / ログアウト --}}
        @else
            <nav class="g-header__nav">
                <a href="{{ url('/attendance') }}">勤怠</a>
                <a href="{{ url('/attendance/list') }}">勤怠一覧</a>
                <a href="{{ url('/stamp_correction_request/list') }}">申請</a>
                <form method="POST" action="{{ route('logout') }}" class="g-header__logout-form">
                    @csrf
                    <button type="submit" class="g-header__logout">ログアウト</button>
                </form>
            </nav>
        @endif
    </div>
</header>
