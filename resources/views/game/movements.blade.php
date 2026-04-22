@extends('layouts.game')

@section('title', 'Bewegungen')

@section('content')
<h2>Armeen & Bewegungen</h2>

<h3>Armee aussenden</h3>
<p>Bewegungskapazität: {{ $town->mov_cap_used }} / {{ $town->mov_cap }}</p>

@if($town->mov_cap_used < $town->mov_cap)
<form method="POST" action="{{ route('game.movements.generate') }}">
    @csrf

    <div>
        <label>Ziel X: <input type="number" name="dest_x" min="0" max="{{ $mapSize - 1 }}" required></label>
        <label>Ziel Y: <input type="number" name="dest_y" min="0" max="{{ $mapSize - 1 }}" required></label>
    </div>

    <div>
        <label>Aktion:
            <select name="action">
                @foreach($actionTypes as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </label>
    </div>

    <h4>Einheiten</h4>
    @if($units->isEmpty())
        <p>Keine Einheiten vorhanden.</p>
    @else
        <table>
            <thead>
                <tr><th>Einheit</th><th>Vorhanden</th><th>Mitsenden</th></tr>
            </thead>
            <tbody>
                @foreach($units as $unit)
                <tr>
                    <td>{{ $unit['name'] }}</td>
                    <td>{{ $unit['quantity'] }}</td>
                    <td><input type="number" name="units[{{ $unit['unit_id'] }}]" value="0" min="0" max="{{ $unit['quantity'] }}"></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h4>Fracht</h4>
    <div>
        <label>Gold: <input type="number" name="gold" value="0" min="0"></label>
        <label>Holz: <input type="number" name="wood" value="0" min="0"></label>
        <label>Stein: <input type="number" name="stone" value="0" min="0"></label>
        <label>Eisen: <input type="number" name="iron" value="0" min="0"></label>
    </div>

    <button type="submit">Aussenden</button>
</form>
@endif

<h3>Aktive Bewegungen</h3>
@if($movements->isEmpty())
    <p>Keine aktiven Bewegungen.</p>
@else
    <table>
        <thead>
            <tr>
                <th>Aktion</th>
                <th>Von</th>
                <th>Nach</th>
                <th>Verbleibend</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movements as $mov)
            @php
                $actionTypes = config('game.game.movement_types');
                $actionName = $actionTypes[$mov->action] ?? 'Unbekannt';
            @endphp
            <tr>
                <td>{{ $actionName }}</td>
                <td>[{{ $mov->start_x }}|{{ $mov->start_y }}]</td>
                <td>[{{ $mov->dest_x }}|{{ $mov->dest_y }}]</td>
                <td>{{ $mov->arrival }} Ticks</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endif
@endsection
