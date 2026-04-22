<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\Town;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Base controller for all authenticated ingame pages.
 * Loads the active user and their selected town, shares them with all views.
 *
 * Ownership fix: town selection uses $user->towns()->findOrFail($townId)
 * instead of the old PHP loop + unchecked ORM load.
 */
abstract class GameController extends Controller
{
    protected User $user;
    protected ?Town $activeTown = null;

    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            $this->user = Auth::user();

            // Load town + buildings/units to avoid N+1 downstream
            $this->user->load(['towns.buildings', 'towns.units', 'techs']);

            // Determine active town from session or default to first town
            $townId = session('active_town_id', $this->user->towns->first()?->id);

            if ($townId) {
                // DB-level ownership check — no PHP loop needed
                $this->activeTown = $this->user->towns()->findOrFail($townId);
                $this->activeTown->load(['buildings', 'units', 'unitProductions']);
            } else {
                // User has no town yet — nothing in the game is usable without one
                abort(503, 'Kein Stadtslot gefunden. Bitte wende dich an den Administrator.');
            }

            $unreadCount = $this->user->messages()
                ->where('unread', true)
                ->count();

            view()->share([
                'user'           => $this->user,
                'activeTown'     => $this->activeTown ?? null,
                'hasUnread'      => $unreadCount > 0,
                'towns'          => $this->user->towns,
            ]);

            return $next($request);
        });
    }

    /**
     * Switch the player's active town.
     * Validates ownership at DB level — a player cannot switch to another player's town.
     */
    public function setTown(Request $request): \Illuminate\Http\RedirectResponse
    {
        $townId = (int) $request->input('town_id');

        // findOrFail will 404 if town doesn't belong to this user
        $town = $this->user->towns()->findOrFail($townId);

        session(['active_town_id' => $town->id]);

        return redirect()->back();
    }
}
