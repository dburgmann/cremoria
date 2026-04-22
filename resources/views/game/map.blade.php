@extends('layouts.game')

@section('title', 'Karte')

@section('content')
<h2>Weltkarte</h2>
<p>Karte {{ $mapSize }}x{{ $mapSize }}</p>

<div class="map-container" style="overflow: auto;">
    <table class="map-grid" style="border-collapse: collapse;">
        @for($y = 0; $y < $mapSize; $y++)
        <tr>
            @for($x = 0; $x < $mapSize; $x++)
            @php
                $townAtCoord = $towns->first(fn($t) => $t->x === $x && $t->y === $y);
            @endphp
            <td style="width:8px;height:8px;border:1px solid #ccc;background:{{ $townAtCoord ? '#666' : '#eee' }}"
                title="{{ $townAtCoord ? $townAtCoord->name . ' [' . $x . '|' . $y . ']' : '' }}">
            </td>
            @endfor
        </tr>
        @endfor
    </table>
</div>

<h3>Alle Städte</h3>
<table>
    <thead>
        <tr><th>Stadt</th><th>Koordinaten</th><th>Aktion</th></tr>
    </thead>
    <tbody>
        @foreach($towns as $t)
        <tr>
            <td>{{ $t->name }}</td>
            <td>[{{ $t->x }}|{{ $t->y }}]</td>
            <td>
                @if(isset($town) && $t->id !== $town->id)
                    <a href="{{ route('game.movements', ['dest_x' => $t->x, 'dest_y' => $t->y]) }}">Angreifen</a>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection
