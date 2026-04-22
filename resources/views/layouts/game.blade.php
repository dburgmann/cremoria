<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cremoria – @yield('title', 'Spiel')</title>
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
</head>
<body>
    <div id="root">
        <div id="banner"></div>

        <div id="infobar">
            <a href="{{ route('game.messages') }}" class="{{ ($hasUnread ?? false) ? 'gotNewMsg' : '' }} msgOpen">
                <img src="{{ asset('images/email.png') }}" alt="Nachrichten">
            </a>
            <img src="{{ asset('images/gold.gif') }}" alt="Gold">{{ number_format($activeTown->gold, 0, ',', '.') }}
            <img src="{{ asset('images/wood.gif') }}" alt="Holz">{{ number_format($activeTown->wood, 0, ',', '.') }}
            <img src="{{ asset('images/stone.gif') }}" alt="Stein">{{ number_format($activeTown->stone, 0, ',', '.') }}
            <img src="{{ asset('images/iron.gif') }}" alt="Eisen">{{ number_format($activeTown->iron, 0, ',', '.') }}
        </div>

        <div id="data">
            <div id="navi">
                @include('partials.navi-in')
            </div>

            <div id="content">
                @if (session('success'))
                    <div class="box center">{{ session('success') }}</div>
                @endif

                @if ($errors->any())
                    <div class="error">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
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
