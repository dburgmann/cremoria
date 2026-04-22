<p>Der Spionageversuch war erfolgreich!</p>
<table>
    <tr>
        <th>Ressource</th>
        <th>Menge</th>
    </tr>
    <tr><td>Gold</td><td>{{ $town->gold }}</td></tr>
    <tr><td>Holz</td><td>{{ $town->wood }}</td></tr>
    <tr><td>Stein</td><td>{{ $town->stone }}</td></tr>
    <tr><td>Eisen</td><td>{{ $town->iron }}</td></tr>
</table>

@if(!empty($units))
<h4>Einheiten</h4>
<table>
    <tr><th>Einheit-ID</th><th>Anzahl</th></tr>
    @foreach($units as $unit)
    <tr><td>{{ $unit['unit_id'] }}</td><td>{{ $unit['quantity'] }}</td></tr>
    @endforeach
</table>
@endif
