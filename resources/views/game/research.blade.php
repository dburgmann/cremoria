@extends('layouts.game')

@section('title', 'Forschung')

@section('content')
<h2>Forschung</h2>
<p>Forschungslabore: {{ $labs }}</p>

@if($currentResearch)
    <div class="current-production">
        <p>Aktuell in Forschung: <strong>{{ $currentResearch['name'] }}</strong>
        (noch {{ auth()->user()->research_time }} Ticks)</p>
    </div>
@endif

@if($labs === 0)
    <p>Kein Forschungslabor gebaut. Baue zuerst ein Forschungslabor.</p>
@elseif(empty($allowed))
    <p>Alle verfügbaren Technologien wurden erforscht.</p>
@else
    <table>
        <thead>
            <tr>
                <th>Technologie</th>
                <th>Typ</th>
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
                <td>{{ $item['type'] }}</td>
                <td>{{ $item['quantity'] }}</td>
                <td>{{ $item['gold'] }}</td>
                <td>{{ $item['wood'] }}</td>
                <td>{{ $item['stone'] }}</td>
                <td>{{ $item['iron'] }}</td>
                <td>{{ $item['time'] }}</td>
                <td>
                    <form method="POST" action="{{ route('game.research.produce') }}">
                        @csrf
                        <input type="hidden" name="item_id" value="{{ $id }}">
                        <button type="submit" {{ $currentResearch ? 'disabled' : '' }}>Erforschen</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endif
@endsection
