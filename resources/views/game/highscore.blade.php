@extends('layouts.game')

@section('title', 'Rangliste')

@section('content')
<h2>Rangliste</h2>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Spieler</th>
            <th>Gesamtpunkte</th>
            <th>Forschungspunkte</th>
            <th>Gebäudepunkte</th>
        </tr>
    </thead>
    <tbody>
        @foreach($players as $i => $player)
        <tr>
            <td>{{ $players->firstItem() + $i }}</td>
            <td>{{ $player->username }}</td>
            <td>{{ $player->total_pts }}</td>
            <td>{{ $player->research_pts }}</td>
            <td>{{ $player->building_pts }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{ $players->links() }}
@endsection
