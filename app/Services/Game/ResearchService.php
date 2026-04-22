<?php

namespace App\Services\Game;

use App\Models\Town;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Handles technology research requests.
 */
class ResearchService
{
    private GameDataRepository $gameData;
    private User $user;
    private Town $town;
    private int $labs;

    /** @var array<int,array<string,mixed>> Techs the user is allowed to research */
    private array $allowedItems = [];

    public function __construct(GameDataRepository $gameData, User $user, Town $town)
    {
        $this->gameData = $gameData;
        $this->user     = $user;
        $this->town     = $town;

        $this->user->loadMissing('techs');
        $this->town->loadMissing('buildings');

        $labId      = (int) config('game.items.research_building', 6);
        $this->labs = $this->town->buildingQuantity($labId);

        $this->allowedItems = ($this->labs > 0) ? $this->buildAllowedItems() : [];
    }

    public function getLabs(): int
    {
        return $this->labs;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getAllowed(): array
    {
        return $this->allowedItems;
    }

    /**
     * Initiates research of a technology.
     *
     * @param int $techId
     * @return string|null  Error code or null on success
     */
    public function produce(int $techId): ?string
    {
        if ($this->labs === 0) {
            return 'prodNoLab';
        }

        if (!isset($this->allowedItems[$techId])) {
            return 'prodForbiddenItem';
        }

        if ($this->user->current_research !== null) {
            return 'prodAlreadyResearching';
        }

        $item = $this->allowedItems[$techId];

        // Non-repeatable techs cannot be researched again
        if (($item['type'] ?? '') !== 'repeatable' && ($item['quantity'] ?? 0) > 0) {
            return 'prodForbiddenItem';
        }

        if (!$this->hasEnoughResources($item)) {
            return 'prodNotEnoughRes';
        }

        DB::transaction(function () use ($techId, $item) {
            $this->deductResources($item);

            $this->user->current_research = $techId;
            $this->user->current_rsc_pts  = GameFormulas::points(
                (int) $item['gold'],
                (int) $item['wood'],
                (int) $item['stone'],
                (int) $item['iron']
            );
            $this->user->research_time = (int) $item['time'];
            $this->user->save();

            $this->town->save();
        });

        return null;
    }

    /**
     * Calculates actual research cost and time, applying lab and level modifiers.
     *
     * @param array<string,mixed> $item
     * @param int $currentLevel  number of times already researched
     */
    public static function calculate(array $item, int $labs, int $currentLevel): array
    {
        $level = $currentLevel + 1; // 1-indexed for formula

        if (($item['type'] ?? '') === 'repeatable') {
            $costMultiplier  = GameFormulas::researchCostMultiplier($currentLevel);
            $item['gold']  = (int) ceil($item['gold']  * $costMultiplier);
            $item['wood']  = (int) ceil($item['wood']  * $costMultiplier);
            $item['stone'] = (int) ceil($item['stone'] * $costMultiplier);
            $item['iron']  = (int) ceil($item['iron']  * $costMultiplier);
        }

        $timeMultiplier = GameFormulas::researchTimeMultiplier($labs, $level);
        $item['time']   = (int) ceil($item['time'] * $timeMultiplier);

        return $item;
    }

    private function buildAllowedItems(): array
    {
        $allowed = [];

        $userTechIds = $this->user->techs->pluck('id')->all();
        $townBldIds  = $this->town->buildings->pluck('id')->all();

        foreach ($this->gameData->allTechs() as $id => $item) {
            if (!$this->meetsRequirements($item, $userTechIds, $townBldIds)) {
                continue;
            }

            $currentQty = $this->user->techQuantity($id);
            $calculated = self::calculate($item, $this->labs, $currentQty);
            $calculated['quantity'] = $currentQty;

            $allowed[$id] = $calculated;
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
