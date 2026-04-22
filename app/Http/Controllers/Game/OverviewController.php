<?php

namespace App\Http\Controllers\Game;

use Illuminate\View\View;

class OverviewController extends GameController
{
    public function index(): View
    {
        return view('game.overview', [
            'town' => $this->activeTown,
            'user' => $this->user,
        ]);
    }
}
