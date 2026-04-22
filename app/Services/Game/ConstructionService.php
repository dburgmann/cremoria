<?php

namespace App\Services\Game;

use App\Models\Town;
use App\Models\User;
use App\Models\BuildingLevel;
use Illuminate\Support\Facades\DB;

/**
 * Handles building construction requests.
 *
 * Bug fixes applied from Kohana ConstructionHandler:
 * - Cost formula: was $gold + $wood + $wood + $wood (wood added 3×).
 *   Fixed to: $gold + $wood + $stone + $iron.
 * - calculateObject / calculateArray DRY violation: unified into one calculate() method.
 */
class ConstructionService
{
    private GameDataRepository $gameData;
    private User $user;
    private Town $town;

    /** @var array<int,array<string,mixed>> Items the user is allowed to build */
    private array $allowedItems = [];

    public function __construct(GameDataRepository $gameData, User $user, Town $town)
    {
        $this->gameData = $gameData;
        $this->user     = $user;
        $this->town     = $town;

        $this->user->loadMissing('techs');
        $this->town->loadMissing('buildings');

        $this->allowedItems = $this->buildAllowedItems();
    }

    /**
     * Returns all buildings the user is allowed to construct in this town.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getAllowed(): array
    {
        return $this->allowedItems;
    }

    /**
     * Initiates construction of a building.
     *
     * @param int $buildingId
     * @return string|null  Error code string, or null on success
     */
    public function produce(int $buildingId): ?string
    {
        if (!isset($this->allowedItems[$buildingId])) {
            return 'prodForbiddenItem';
        }

        if ($this->town->current_building !== null) {
            return 'prodAlreadyBuilding';
        }

        $item = $this->allowedItems[$buildingId];

        if (!$this->hasEnoughResources($item)) {
            return 'prodNotEnoughRes';
        }

        DB::transaction(function () use ($buildingId, $item) {
            $this->deductResources($item);

            $this->town->current_building = $buildingId;
            $this->town->current_bld_pts  = GameFormulas::points(
                (int) $item['gold'],
                (int) $item['wood'],
                (int) $item['stone'],
                (int) $item['iron']
            );
            $this->town->building_time = (int) $item['time'];
            $this->town->save();
        });

        return null;
    }

    /**
     * Calculates the actual cost of a building given how many the town already has.
     *
     * @param array<string,mixed> $item        base item data from GameDataRepository
     * @param int                 $existingQty number of this building already built
     * @return array<string,mixed>
     */
    public static function calculate(array $item, int $existingQty): array
    {
        $multiplier = GameFormulas::buildingCostMultiplier($existingQty);
        $divider    = (int) config('game.game.building_time_divider', 1000);

        $item['gold']  = (int) ceil($item['gold'] * $multiplier);
        $item['wood']  = (int) ceil($item['wood'] * $multiplier);
        $item['stone'] = (int) ceil($item['stone'] * $multiplier);
        $item['iron']  = (int) ceil($item['iron'] * $multiplier);

        // Bug fix: original code used $wood + $wood + $wood instead of $wood + $stone + $iron
        $sum          = $item['gold'] + $item['wood'] + $item['stone'] + $item['iron'];
        $item['time'] = (int) ceil(($item['time'] ?? 0) + ($sum / $divider));

        return $item;
    }

    /**
     * Builds the allowed items list, checking tech and building requirements.
     *
     * @return array<int,array<string,mixed>>
     */
    private function buildAllowedItems(): array
    {
        $allowed = [];

        $userTechIds = $this->user->techs->pluck('id')->all();
        $townBldIds  = $this->town->buildings->pluck('id')->all();

        foreach ($this->gameData->allBuildings() as $id => $item) {
            if (!$this->meetsRequirements($item, $userTechIds, $townBldIds)) {
                continue;
            }

            $existingQty = $this->town->buildingQuantity($id);
            $calculated  = self::calculate($item, $existingQty);
            $calculated['quantity'] = $existingQty;

            $allowed[$id] = $calculated;
        }

        return $allowed;
    }

    /**
     * Checks whether all required techs and buildings are present.
     *
     * @param array<string,mixed> $item
     * @param int[]               $userTechIds
     * @param int[]               $townBldIds
     */
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

    private function hasEnoughResources(array $item): bool
    {
        return $this->town->gold  >= $item['gold']
            && $this->town->wood  >= $item['wood']
            && $this->town->stone >= $item['stone']
            && $this->town->iron  >= $item['iron'];
    }

    private function deductResources(array $item): void
    {
        $this->town->gold  -= (int) $item['gold'];
        $this->town->wood  -= (int) $item['wood'];
        $this->town->stone -= (int) $item['stone'];
        $this->town->iron  -= (int) $item['iron'];
    }
}
