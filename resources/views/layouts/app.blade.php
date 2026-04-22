<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cremoria – @yield('title', 'Strategie-Spiel')</title>
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
</head>
<body>
    <div id="root">
        <div id="banner"></div>
        <div id="infobar">
            @yield('infobar')
        </div>
        <div id="data">
            <div id="navi">
                @yield('navi', view('partials.navi-out'))
            </div>
            <div id="content">
                @if (session('status'))
                    <div class="alert">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="error">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>
            <br class="clear">
        </div>
        <div id="bottom"></div>
    </div>
</body>
</html>
