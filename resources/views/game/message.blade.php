@extends('layouts.game')

@section('title', $message->title)

@section('content')
<h2>{{ $message->title }}</h2>
<div class="message-text">{!! $message->text !!}</div>

<a href="{{ route('game.messages') }}">&larr; Zurück zur Übersicht</a>

<form method="POST" action="{{ route('game.messages.destroy', $message->id) }}">
    @csrf
    @method('DELETE')
    <button type="submit" onclick="return confirm('Nachricht löschen?')">Löschen</button>
</form>
@endsection
