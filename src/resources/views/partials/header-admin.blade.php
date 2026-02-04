<header class="g-header">
    <div class="g-header__inner">
        {{-- ロゴ --}}
        <a class="g-header__brand" href="{{ url('/admin/attendance/list') }}">
            <img class="g-header__logo"
                src="{{ asset('images/logo.svg') }}"
                alt="COACHTECH">
        </a>

        {{-- 管理者ログイン画面：ロゴのみ --}}
        @if ($type === 'login')
            <div class="g-header__spacer"></div>

        {{-- 管理者ログイン後 --}}
        @else
            <nav class="g-header__nav">
                <a href="{{ url('/admin/attendance/list') }}">勤怠一覧</a>
                <a href="{{ url('/admin/staff/list') }}">スタッフ一覧</a>
                <a href="{{ url('/stamp_correction_request/list') }}">申請一覧</a>

                <form method="POST" action="{{ url('/admin/logout') }}" class="g-header__logout">
                    @csrf
                    <button type="submit">ログアウト</button>
                </form>
            </nav>
        @endif
    </div>
</header>
