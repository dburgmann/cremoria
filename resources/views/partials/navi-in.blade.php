@if($towns->count() > 1)
<form method="POST" action="{{ route('game.setTown') }}" id="townSelection">
    @csrf
    <select name="town_id" onchange="this.form.submit()">
        @foreach($towns as $town)
            <option value="{{ $town->id }}" {{ $activeTown->id === $town->id ? 'selected' : '' }}>
                {{ $town->name }} [{{ $town->x }}|{{ $town->y }}]
            </option>
        @endforeach
    </select>
</form>
@endif

<a href="{{ route('game.overview') }}" class="btn">Übersicht</a>
<a href="{{ route('game.construction') }}" class="btn">Gebäude</a>
<a href="{{ route('game.research') }}" class="btn">Forschung</a>
<a href="{{ route('game.recruitment') }}" class="btn">Einheiten</a>
<a href="{{ route('game.map') }}" class="btn">Karte</a>
<a href="{{ route('game.movements') }}" class="btn">Armee</a>
<a href="{{ route('game.messages') }}" class="btn">Nachrichten</a>
<a href="{{ route('game.highscore') }}" class="btn">Highscore</a>

<form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit" class="btn">Logout</button>
</form>
