<?php

namespace App\Services\Game;

use App\Models\Town;
use App\Models\User;

/**
 * Handles espionage missions: success calculation and report generation.
 * Fixes: $townNo undefined variable bug from Kohana Espionage class.
 */
class EspionageService
{
    private User $doer;
    private User $victim;
    private Town $destTown;

    public function __construct(User $doer, User $victim, Town $destTown)
    {
        $this->doer     = $doer;
        $this->victim   = $victim;
        $this->destTown = $destTown;
    }

    /**
     * Determines whether the espionage attempt succeeded.
     */
    public function isSuccessful(): bool
    {
        $doerPower   = $this->getSpioPower($this->doer);
        $victimPower = $this->getDiscoverPower($this->destTown);
        $chance      = GameFormulas::espionageSuccessChance($doerPower, $victimPower);
        return mt_rand(0, 100) < $chance;
    }

    /**
     * Generates the HTML report for the espionage mission.
     *
     * @param bool $success  whether the espionage succeeded
     */
    public function getReport(bool $success): string
    {
        if ($success) {
            $units = $this->destTown->units->map(function ($u) {
                return ['unit_id' => $u->unit_id, 'quantity' => $u->quantity];
            })->toArray();

            return view('game.espionage.report-success', [
                'town' => $this->destTown,
                'units' => $units,
            ])->render();
        }

        return view('game.espionage.report-failure')->render();
    }

    /**
     * Returns the maximum spy building level across all of the user's towns.
     * Bug fix: original code had `$town = $town->quantityOfBuilding(...)` which
     * overwrote the loop variable, then used undefined `$townNo`.
     */
    private function getSpioPower(User $user): int
    {
        $bldId = (int) config('game.items.spio_def_building');
        $max   = 0;

        // Ensure towns+buildings are loaded in one query
        $user->loadMissing('towns.buildings');

        foreach ($user->towns as $town) {
            $level = $town->buildingQuantity($bldId);
            if ($level > $max) {
                $max = $level;
            }
        }

        return $max;
    }

    /**
     * Returns the espionage defence power of the given town.
     */
    private function getDiscoverPower(Town $town): int
    {
        $bldId = (int) config('game.items.spio_def_building');
        $town->loadMissing('buildings');
        return $town->buildingQuantity($bldId);
    }
}
