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
        <style>
        .header__inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #000;
            padding: 10px 20px;
        }

        .header__image {
            height: 40px;
        }

        .header-nav {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .nav-link {
            color: #fff;
            text-decoration: none;
            margin-right: 16px;
            font-weight: bold;
            font-size: 16px;
        }

        .nav-link:last-child {
            margin-right: 0;
        }

        .header-nav form {
            margin: 0;
        }

        .alert-danger {
            color: red;
        }
    </style>
</header>