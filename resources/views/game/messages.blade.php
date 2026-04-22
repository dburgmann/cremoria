@extends('layouts.game')

@section('title', 'Nachrichten')

@section('content')
<h2>Nachrichten</h2>

@if($messages->isEmpty())
    <p>Keine Nachrichten.</p>
@else
    <table>
        <thead>
            <tr>
                <th>Betreff</th>
                <th>Datum</th>
                <th>Aktion</th>
            </tr>
        </thead>
        <tbody>
            @foreach($messages as $msg)
            <tr class="{{ $msg->unread ? 'unread' : '' }}">
                <td><a href="{{ route('game.messages.show', $msg->id) }}">{{ $msg->title }}</a></td>
                <td>{{ $msg->created_at?->format('d.m.Y H:i') }}</td>
                <td>
                    <form method="POST" action="{{ route('game.messages.destroy', $msg->id) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" onclick="return confirm('Nachricht löschen?')">Löschen</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $messages->links() }}
@endif
@endsection
