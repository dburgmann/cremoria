@extends('layouts.game')

@section('title', 'Rekrutierung')

@section('content')
<h2>Einheiten rekrutieren</h2>

@if(!empty($production))
    <h3>In Produktion</h3>
    <table>
        <thead>
            <tr><th>Einheit</th><th>Anzahl</th><th>Verbleibend</th></tr>
        </thead>
        <tbody>
            @foreach($production as $prod)
            <tr>
                <td>{{ $prod['name'] }}</td>
                <td>{{ $prod['quantity'] }}</td>
                <td>{{ $prod['time'] }} Ticks</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endif

<h3>Verfügbare Einheiten</h3>
<p>Kapazität: {{ $town->unit_cap_used }} / {{ $town->unit_cap }}</p>

@if(empty($allowed))
    <p>Keine Einheiten verfügbar. Benötigte Technologien oder Kaserne fehlen.</p>
@else
    <form method="POST" action="{{ route('game.recruitment.produce') }}">
        @csrf
        <table>
            <thead>
                <tr>
                    <th>Einheit</th>
                    <th>Gold</th>
                    <th>Holz</th>
                    <th>Stein</th>
                    <th>Eisen</th>
                    <th>Zeit</th>
                    <th>AP</th>
                    <th>HP</th>
                    <th>Anzahl</th>
                </tr>
            </thead>
            <tbody>
                @foreach($allowed as $id => $item)
                <tr>
                    <td>{{ $item['name'] }}</td>
                    <td>{{ $item['gold'] }}</td>
                    <td>{{ $item['wood'] }}</td>
                    <td>{{ $item['stone'] }}</td>
                    <td>{{ $item['iron'] }}</td>
                    <td>{{ $item['time'] }}</td>
                    <td>{{ $item['ap'] }}</td>
                    <td>{{ $item['hp'] }}</td>
                    <td>
                        <input type="number" name="units[{{ $id }}]" value="0" min="0">
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <button type="submit">Rekrutieren</button>
    </form>
@endif
@endsection
