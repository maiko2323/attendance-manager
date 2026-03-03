<header class="g-header">
    <div class="g-header__inner">
        <a class="g-header__brand" href="{{ url('/admin/attendance/list') }}">
            <img class="g-header__logo" src="{{ asset('images/logo.svg') }}" alt="COACHTECH">
        </a>

        @php
            $type = $type ?? 'default';
        @endphp

        @if ($type === 'login')
            <div class="g-header__spacer"></div>
        @else
            <nav class="g-header__nav">
                <a href="{{ route('admin.attendance.list') }}">勤怠一覧</a>
                <a href="{{ route('admin.staff.list') }}">スタッフ一覧</a>
                <a href="{{ route('stamp.request.list') }}">申請一覧</a>

                <form method="POST" action="{{ route('admin.logout') }}" class="g-header__logout">
                    @csrf
                    <button type="submit" class="g-header__logout">ログアウト</button>
                </form>
            </nav>
        @endif
    </div>
</header>
