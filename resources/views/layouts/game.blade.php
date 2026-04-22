<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cremoria – @yield('title', 'Spiel')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    {{-- Resource bar --}}
    @if(isset($activeTown))
    <div class="resource-bar">
        <span>Gold: {{ $activeTown->gold }}</span>
        <span>Holz: {{ $activeTown->wood }}</span>
        <span>Stein: {{ $activeTown->stone }}</span>
        <span>Eisen: {{ $activeTown->iron }}</span>
        @if($hasUnread ?? false)
            <a href="{{ route('game.messages') }}" class="unread-indicator">&#9993; Neue Nachrichten</a>
        @endif
    </div>
    @endif

    {{-- Town selection --}}
    @if(isset($towns) && $towns->count() > 1)
    <form method="POST" action="{{ route('game.setTown') }}">
        @csrf
        <select name="town_id" onchange="this.form.submit()">
            @foreach($towns as $town)
                <option value="{{ $town->id }}"
                    {{ isset($activeTown) && $activeTown->id === $town->id ? 'selected' : '' }}>
                    {{ $town->name }} [{{ $town->x }}|{{ $town->y }}]
                </option>
            @endforeach
        </select>
    </form>
    @endif

    {{-- Navigation --}}
    <nav>
        <a href="{{ route('game.overview') }}">Übersicht</a>
        <a href="{{ route('game.construction') }}">Bauen</a>
        <a href="{{ route('game.recruitment') }}">Rekrutierung</a>
        <a href="{{ route('game.research') }}">Forschung</a>
        <a href="{{ route('game.movements') }}">Bewegungen</a>
        <a href="{{ route('game.messages') }}">Nachrichten</a>
        <a href="{{ route('game.map') }}">Karte</a>
        <a href="{{ route('game.highscore') }}">Rangliste</a>
        <form method="POST" action="{{ route('logout') }}" style="display:inline">
            @csrf
            <button type="submit">Ausloggen</button>
        </form>
    </nav>

    <div class="content">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @yield('content')
    </div>
</body>
</html>
