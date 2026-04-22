<?php

namespace App\Http\Controllers\Game;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Handles town switching. Standalone controller to avoid routing abstract class.
 */
class TownController extends GameController
{
    public function setTown(Request $request): RedirectResponse
    {
        $townId = (int) $request->input('town_id');

        // DB-level ownership check — findOrFail returns 404 if town belongs to another user
        $town = $this->user->towns()->findOrFail($townId);

        session(['active_town_id' => $town->id]);

        return redirect()->back();
    }
}
