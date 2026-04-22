@extends('layouts.game')

@section('title', 'Bauen')

@section('content')
<h2>Gebäude</h2>

@if($currentBuilding)
    <div class="current-production">
        <p>Aktuell im Bau: <strong>{{ $currentBuilding['name'] }}</strong>
        (noch {{ $town->building_time }} Ticks)</p>
    </div>
@endif

@if(empty($allowed))
    <p>Keine Gebäude verfügbar.</p>
@else
    <table>
        <thead>
            <tr>
                <th>Gebäude</th>
                <th>Stufe</th>
                <th>Gold</th>
                <th>Holz</th>
                <th>Stein</th>
                <th>Eisen</th>
                <th>Zeit (Ticks)</th>
                <th>Aktion</th>
            </tr>
        </thead>
        <tbody>
            @foreach($allowed as $id => $item)
            <tr>
                <td>{{ $item['name'] }}</td>
                <td>{{ $item['quantity'] }}</td>
                <td>{{ $item['gold'] }}</td>
                <td>{{ $item['wood'] }}</td>
                <td>{{ $item['stone'] }}</td>
                <td>{{ $item['iron'] }}</td>
                <td>{{ $item['time'] }}</td>
                <td>
                    <form method="POST" action="{{ route('game.construction.produce') }}">
                        @csrf
                        <input type="hidden" name="item_id" value="{{ $id }}">
                        <button type="submit" {{ $currentBuilding ? 'disabled' : '' }}>Bauen</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endif
@endsection
