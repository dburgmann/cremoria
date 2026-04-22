<?php

namespace App\Http\Controllers\Game;

use App\Models\Town;
use Illuminate\View\View;

class MapController extends GameController
{
    public function index(): View
    {
        // Load all town positions for the map
        $towns = Town::select(['id', 'x', 'y', 'name', 'user_id'])->get();

        return view('game.map', [
            'towns'    => $towns,
            'mapSize'  => (int) config('game.game.map_size', 100),
            'town'     => $this->activeTown,
        ]);
    }
}
