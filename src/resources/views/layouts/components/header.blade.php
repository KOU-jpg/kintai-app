<header>
    <div class="header__inner">
        @yield('header')
        <a href="{{ route('attendance')}}">
            <img src="{{ asset('images/logo.svg') }}" class="header__image" alt="coachtechロゴ"></a>
        <nav class="header-nav">
            <a href="{{ route('attendance') }}" class="nav-link">勤怠</a>
            <a href="{{ route('index') }}" class="nav-link">勤怠一覧</a>
            <a href="{{ route('request') }}" class="nav-link">申請</a>

            <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                @csrf
                <button type="submit" class="nav-link" style="background:none;border:none;padding:0;cursor:pointer;">
                    ログアウト
                </button>
            </form>
        </nav>
    </div>
</header>