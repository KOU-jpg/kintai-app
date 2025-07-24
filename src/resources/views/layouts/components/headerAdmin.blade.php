<header>
    <div class="header__inner">
        @yield('header')
        <a href="{{ route('admin.attendance.list')}}">
            <img src="{{ asset('images/logo.svg') }}" class="header__image" alt="coachtechロゴ"></a>
        <nav class="header-nav">
            @if(Auth::check() && Auth::user()->hasVerifiedEmail())
            <a href="{{ route('admin.attendance.list') }}" class="nav-link">勤怠一覧</a>
            <a href="{{ route('admin.staff.list') }}" class="nav-link">スタッフ一覧</a>
            <a href="{{ route('request') }}" class="nav-link">申請一覧</a>

            <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                @csrf
                <button type="submit" class="nav-link" style="background:none;border:none;padding:0;cursor:pointer;">
                    ログアウト
                </button>
            </form>
            @else
            <a href="{{ route('loginView') }}" class="nav-link">ログイン</a>
            @endif
        </nav>
    </div>
</header>