<?php

namespace App\Services\Game;

use App\Models\UnitStock;

/**
 * Defending army — casualties are removed from the town's units table.
 */
class DefenderArmy extends Army
{
    /**
     * Persist casualties back to the units (unit stock) table.
     *
     * @param int $contextId  town ID
     */
    public function save(int $contextId): void
    {
        foreach ($this->casualties as $unitId => $cas) {
            $stock = UnitStock::where('unit_id', $unitId)
                ->where('town_id', $contextId)
                ->first();

            if ($stock === null) {
                continue;
            }

            if ($cas->quantity >= $stock->quantity) {
                $stock->delete();
            } else {
                $stock->decrement('quantity', $cas->quantity);
            }
        }
    }
}
