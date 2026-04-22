<?php

namespace App\Services\Game;

use App\Models\Troop;

/**
 * Attacking army — casualties are removed from the movement's troops table.
 */
class AttackerArmy extends Army
{
    /**
     * Persist casualties back to the troops table.
     *
     * @param int $contextId  movement ID
     */
    public function save(int $contextId): void
    {
        foreach ($this->casualties as $unitId => $cas) {
            $troop = Troop::where('unit_id', $unitId)
                ->where('movement_id', $contextId)
                ->first();

            if ($troop === null) {
                continue;
            }

            if ($cas->quantity >= $troop->quantity) {
                $troop->delete();
            } else {
                $troop->decrement('quantity', $cas->quantity);
            }
        }
    }
}
