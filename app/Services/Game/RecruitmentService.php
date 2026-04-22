<?php

namespace App\Services\Game;

use App\Models\Town;
use App\Models\User;
use App\Models\UnitProduction;
use Illuminate\Support\Facades\DB;

/**
 * Handles unit recruitment: checks requirements, deducts resources, queues production.
 */
class RecruitmentService
{
    private GameDataRepository $gameData;
    private User $user;
    private Town $town;

    /** @var array<int,array<string,mixed>> */
    private array $allowedItems = [];

    public function __construct(GameDataRepository $gameData, User $user, Town $town)
    {
        $this->gameData = $gameData;
        $this->user     = $user;
        $this->town     = $town;

        $this->user->loadMissing('techs');
        $this->town->loadMissing('buildings', 'units');

        $this->allowedItems = $this->buildAllowedItems();
    }

    /**
     * Returns all units the user is allowed to recruit in this town.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getAllowed(): array
    {
        return $this->allowedItems;
    }

    /**
     * Queues recruitment for a list of unit orders.
     *
     * @param array<int,int> $orders  unit_id => quantity
     * @return string|null  Error code or null on success
     */
    public function produce(array $orders): ?string
    {
        // Validate all orders first, then commit all at once
        $freeCap = $this->town->unit_cap - $this->town->unit_cap_used;
        $totalOrdered = array_sum($orders);

        if ($totalOrdered <= 0) {
            return 'prodNoUnitsSelected';
        }

        if ($freeCap < $totalOrdered) {
            return 'prodNotEnoughCap';
        }

        foreach ($orders as $unitId => $qty) {
            if ($qty <= 0) {
                continue;
            }

            if (!isset($this->allowedItems[$unitId])) {
                return 'prodForbiddenItem';
            }

            $item = $this->allowedItems[$unitId];

            if (!$this->hasEnoughResources($item, $qty)) {
                return 'prodNotEnoughRes';
            }
        }

        // Commit
        DB::transaction(function () use ($orders) {
            foreach ($orders as $unitId => $qty) {
                if ($qty <= 0) {
                    continue;
                }

                $item = $this->allowedItems[$unitId];
                $this->deductResources($item, $qty);

                // Increment existing queue entry or create new one
                $existing = UnitProduction::where('unit_id', $unitId)
                    ->where('town_id', $this->town->id)
                    ->where('time', (int) $item['time'])
                    ->first();

                if ($existing !== null) {
                    $existing->increment('quantity', $qty);
                } else {
                    UnitProduction::create([
                        'unit_id'  => $unitId,
                        'town_id'  => $this->town->id,
                        'quantity' => $qty,
                        'time'     => (int) $item['time'],
                    ]);
                }

                $this->town->increment('unit_cap_used', $qty);
            }

            $this->town->save();
        });

        return null;
    }

    /**
     * Returns all units currently in the production queue for this town.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getProduction(): array
    {
        $this->town->load('unitProductions');
        $result = [];

        foreach ($this->town->unitProductions as $prod) {
            $unitData = $this->gameData->unit((int) $prod->unit_id);
            if ($unitData === null) {
                continue;
            }
            $result[] = array_merge($unitData, [
                'quantity' => $prod->quantity,
                'time'     => $prod->time,
            ]);
        }

        return $result;
    }

    private function buildAllowedItems(): array
    {
        $allowed = [];

        $userTechIds = $this->user->techs->pluck('id')->all();
        $townBldIds  = $this->town->buildings->pluck('id')->all();

        foreach ($this->gameData->allUnits() as $id => $item) {
            if (!$this->meetsRequirements($item, $userTechIds, $townBldIds)) {
                continue;
            }

            $allowed[$id] = $item;
        }

        return $allowed;
    }

    private function meetsRequirements(array $item, array $userTechIds, array $townBldIds): bool
    {
        foreach ($item['req_tech'] ?? [] as $techId) {
            if (!in_array($techId, $userTechIds, true)) {
                return false;
            }
        }
        foreach ($item['req_bld'] ?? [] as $bldId) {
            if (!in_array($bldId, $townBldIds, true)) {
                return false;
            }
        }
        return true;
    }

    private function hasEnoughResources(array $item, int $qty): bool
    {
        return $this->town->gold  >= ($item['gold']  * $qty)
            && $this->town->wood  >= ($item['wood']  * $qty)
            && $this->town->stone >= ($item['stone'] * $qty)
            && $this->town->iron  >= ($item['iron']  * $qty);
    }

    private function deductResources(array $item, int $qty): void
    {
        $this->town->gold  -= (int) $item['gold']  * $qty;
        $this->town->wood  -= (int) $item['wood']  * $qty;
        $this->town->stone -= (int) $item['stone'] * $qty;
        $this->town->iron  -= (int) $item['iron']  * $qty;
    }
}
