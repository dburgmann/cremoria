@extends('layouts.app')

@section('title', 'Anmelden')

@section('content')
<form method="POST" action="{{ route('login') }}" id="loginForm">
    @csrf

    <div>
        <label for="username">Benutzername</label>
        <input type="text" id="username" name="username" value="{{ old('username') }}" required autofocus>
        @error('username')
            <span class="error">{{ $message }}</span>
        @enderror
    </div>

    <div>
        <label for="password">Passwort</label>
        <input type="password" id="password" name="password" required>
        @error('password')
            <span class="error">{{ $message }}</span>
        @enderror
    </div>

    <div>
        <label>
            <input type="checkbox" name="remember"> Angemeldet bleiben
        </label>
    </div>

    <button type="submit" class="btn">Anmelden</button>
</form>

<p>Noch kein Konto? <a href="{{ route('register') }}">Jetzt registrieren</a></p>
@endsection
