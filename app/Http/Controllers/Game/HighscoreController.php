<?php

namespace App\Http\Controllers\Game;

use App\Models\User;
use Illuminate\View\View;

class HighscoreController extends GameController
{
    public function index(): View
    {
        $players = User::select(['id', 'username', 'total_pts', 'research_pts', 'building_pts'])
            ->orderByDesc('total_pts')
            ->paginate(50);

        return view('game.highscore', [
            'players' => $players,
        ]);
    }
}
