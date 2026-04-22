@extends('layouts.game')

@section('title', 'Übersicht')

@section('content')
<h2>Übersicht – {{ $town->name }}</h2>

<div class="town-info">
    <h3>Ressourcen</h3>
    <table>
        <tr>
            <td>Gold:</td><td>{{ $town->gold }} (+{{ $town->gold_inc }}/Tick)</td>
        </tr>
        <tr>
            <td>Holz:</td><td>{{ $town->wood }} (+{{ $town->wood_inc }}/Tick)</td>
        </tr>
        <tr>
            <td>Stein:</td><td>{{ $town->stone }} (+{{ $town->stone_inc }}/Tick)</td>
        </tr>
        <tr>
            <td>Eisen:</td><td>{{ $town->iron }} (max {{ $town->iron_inc_max }}/Tick)</td>
        </tr>
    </table>
</div>

<div class="town-status">
    <h3>Status</h3>
    @if($town->current_building)
        <p>Im Bau: Gebäude #{{ $town->current_building }} (noch {{ $town->building_time }} Ticks)</p>
    @else
        <p>Kein Gebäude im Bau.</p>
    @endif

    @if($user->current_research)
        <p>In Forschung: Technologie #{{ $user->current_research }} (noch {{ $user->research_time }} Ticks)</p>
    @else
        <p>Keine aktive Forschung.</p>
    @endif

    <p>Einheitenkapazität: {{ $town->unit_cap_used }} / {{ $town->unit_cap }}</p>
    <p>Bewegungskapazität: {{ $town->mov_cap_used }} / {{ $town->mov_cap }}</p>
    <p>Punkte: {{ $user->total_pts }}</p>
</div>
@endsection
