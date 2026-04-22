@extends('layouts.app')

@section('title', 'Registrieren')

@section('content')
<h1>Cremoria</h1>
<h2>Neues Konto erstellen</h2>

<form method="POST" action="{{ route('register') }}">
    @csrf

    <div>
        <label for="username">Benutzername</label>
        <input type="text" id="username" name="username" value="{{ old('username') }}" required>
        @error('username')
            <span class="error">{{ $message }}</span>
        @enderror
    </div>

    <div>
        <label for="email">E-Mail-Adresse</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required>
        @error('email')
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
        <label for="password_confirmation">Passwort bestätigen</label>
        <input type="password" id="password_confirmation" name="password_confirmation" required>
    </div>

    <button type="submit">Registrieren</button>
</form>

<p>Bereits registriert? <a href="{{ route('login') }}">Anmelden</a></p>
@endsection
