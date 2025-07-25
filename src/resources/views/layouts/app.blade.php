<!DOCTYPE html>
<html lang="en"> <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
    <link rel="stylesheet" href="{{ asset('css/common.css')}}">
    @yield('css')
</head>

<body>
    <div class="app">
        <header class="header">
            <div class="header__inner">
                <div class="header-utilities">

                    <a href="/">
                        <img src="{{ asset('images/logo.svg') }}" alt="COACHTECH">
                    </a>

                    <nav>
                        <ul class="header-nav-list">
                            @if (Auth::check())
                                <li class="header-nav-item"><a class="header-nav__link" href="/attendance/index">勤怠</a></li>
                                <li class="header-nav-item"><a class="header-nav__link" href="/attendance/list">勤怠一覧</a></li>
                                <li class="header-nav-item">  <form class="form" action="/logout" method="post">
                                        @csrf
                                        <button class="header-nav__button">ログアウト</button>
                                    </form>
                                </li>
                            @endif
                        </ul>
                    </nav>
        
                </div>
            </div>
        </header>
        <div class="content">
            @yield('content')
        </div>
    </div>

    @yield('scripts') 

</body>

</html>