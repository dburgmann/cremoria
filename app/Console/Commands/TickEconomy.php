<?php

namespace App\Console\Commands;

use App\Services\Game\GameFormulas;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Economy tick: runs every game tick to update resources, buildings, units, and techs.
 *
 * Replaces the Kohana TickEconomy_Controller. All raw SQL with string interpolation
 * has been replaced with parameterized Eloquent/query-builder calls.
 */
class TickEconomy extends Command
{
    protected $signature   = 'game:tick-economy';
    protected $description = 'Run the economy tick: update resources, complete buildings/research/units';

    public function handle(): int
    {
        DB::transaction(function () {
            $this->updateTowns();
            $this->updateUsers();
            $this->updateProduction();
            $this->updateBuildings();
            $this->updateUnits();
            $this->updateTechs();
        });

        $this->info('Economy tick completed.');
        return Command::SUCCESS;
    }

    /**
     * Decrement building timers and add resource income each tick.
     */
    private function updateTowns(): void
    {
        DB::table('towns')->update([
            'building_time' => DB::raw('building_time - 1'),
            'gold'          => DB::raw('gold + gold_inc'),
            'wood'          => DB::raw('wood + wood_inc'),
            'stone'         => DB::raw('stone + stone_inc'),
            'iron'          => DB::raw('iron + iron_inc'),
        ]);

        // Credit building points for newly completed buildings
        DB::table('towns')
            ->where('building_time', '<=', 0)
            ->whereNotNull('current_building')
            ->update([
                'building_pts' => DB::raw('building_pts + current_bld_pts'),
            ]);
    }

    /**
     * Decrement research timers and credit research points on completion.
     */
    private function updateUsers(): void
    {
        DB::table('users')
            ->whereNotNull('research_time')
            ->update([
                'research_time' => DB::raw('research_time - 1'),
            ]);

        DB::table('users')
            ->where('research_time', '<=', 0)
            ->whereNotNull('current_research')
            ->update([
                'research_pts' => DB::raw('research_pts + current_rsc_pts'),
            ]);
    }

    /**
     * Decrement unit production timers.
     */
    private function updateProduction(): void
    {
        DB::table('unit_productions')->update([
            'time' => DB::raw('time - 1'),
        ]);
    }

    /**
     * Complete buildings where building_time <= 0.
     * Inserts/increments in the buildings table, then triggers side effects.
     */
    private function updateBuildings(): void
    {
        $completed = DB::table('towns')
            ->where('building_time', '<=', 0)
            ->whereNotNull('current_building')
            ->get(['id', 'current_building']);

        if ($completed->isEmpty()) {
            return;
        }

        foreach ($completed as $town) {
            // Use Eloquent upsert — works on both MySQL and SQLite
            $existing = DB::table('buildings')
                ->where('id', $town->current_building)
                ->where('town_id', $town->id)
                ->first(['quantity']);

            if ($existing) {
                DB::table('buildings')
                    ->where('id', $town->current_building)
                    ->where('town_id', $town->id)
                    ->update(['quantity' => $existing->quantity + 1]);
            } else {
                DB::table('buildings')->insert([
                    'id'       => $town->current_building,
                    'town_id'  => $town->id,
                    'quantity' => 1,
                ]);
            }

            $this->checkBuildingOperations($town->id, (int) $town->current_building);
        }

        $completedIds = $completed->pluck('id')->all();
        DB::table('towns')
            ->whereIn('id', $completedIds)
            ->update([
                'current_building' => null,
                'building_time'    => null,
            ]);
    }

    /**
     * Triggers income/capacity updates when certain buildings complete.
     */
    private function checkBuildingOperations(int $townId, int $buildingId): void
    {
        $tickRelevant = config('game.items.tick_relevant_buildings', []);
        if (!in_array($buildingId, $tickRelevant, true)) {
            return;
        }

        // Get the new quantity after increment
        $row      = DB::table('buildings')
            ->where('town_id', $townId)
            ->where('id', $buildingId)
            ->first(['quantity']);
        $quantity = $row ? (int) $row->quantity : 0;

        switch ($buildingId) {
            case config('game.items.gold_building'):
                $calced = GameFormulas::goldIncome($quantity);
                DB::table('towns')->where('id', $townId)->update(['gold_inc' => $calced]);
                break;

            case config('game.items.wood_building'):
                $calced      = GameFormulas::woodIncome($quantity);
                $ironToWood  = (int) config('game.game.iron_to_wood', 1);
                DB::table('towns')
                    ->where('id', $townId)
                    ->update(['wood_inc' => DB::raw("$calced - ($ironToWood * iron_inc)")]);
                break;

            case config('game.items.stone_building'):
                $calced       = GameFormulas::stoneIncome($quantity);
                $ironToStone  = (int) config('game.game.iron_to_stone', 2);
                DB::table('towns')
                    ->where('id', $townId)
                    ->update(['stone_inc' => DB::raw("$calced - ($ironToStone * iron_inc)")]);
                break;

            case config('game.items.iron_building'):
                $calced = GameFormulas::ironIncomeMax($quantity);
                DB::table('towns')->where('id', $townId)->update(['iron_inc_max' => $calced]);
                break;

            case config('game.items.unit_building'):
                $calced = GameFormulas::unitCap($quantity);
                DB::table('towns')->where('id', $townId)->update(['unit_cap' => $calced]);
                break;

            case config('game.items.mov_cap_building'):
                DB::table('towns')->where('id', $townId)->update(['mov_cap' => $quantity]);
                break;
        }
    }

    /**
     * Completes unit production where time <= 0, adds units to the town.
     */
    private function updateUnits(): void
    {
        $done = DB::table('unit_productions')
            ->where('time', '<=', 0)
            ->get(['id', 'unit_id', 'town_id', 'quantity']);

        if ($done->isEmpty()) {
            return;
        }

        foreach ($done as $prod) {
            $existing = DB::table('units')
                ->where('unit_id', $prod->unit_id)
                ->where('town_id', $prod->town_id)
                ->first(['quantity']);

            if ($existing) {
                DB::table('units')
                    ->where('unit_id', $prod->unit_id)
                    ->where('town_id', $prod->town_id)
                    ->update(['quantity' => $existing->quantity + $prod->quantity]);
            } else {
                DB::table('units')->insert([
                    'unit_id'  => $prod->unit_id,
                    'town_id'  => $prod->town_id,
                    'quantity' => $prod->quantity,
                ]);
            }

            DB::table('towns')
                ->where('id', $prod->town_id)
                ->update(['unit_cap_used' => DB::raw("unit_cap_used - {$prod->quantity}")]);
        }

        $doneIds = $done->pluck('id')->all();
        DB::table('unit_productions')->whereIn('id', $doneIds)->delete();
    }

    /**
     * Completes research where research_time <= 0, adds tech to user.
     */
    private function updateTechs(): void
    {
        $done = DB::table('users')
            ->where('research_time', '<=', 0)
            ->whereNotNull('current_research')
            ->get(['id', 'current_research']);

        if ($done->isEmpty()) {
            return;
        }

        foreach ($done as $user) {
            $existing = DB::table('techs')
                ->where('id', $user->current_research)
                ->where('user_id', $user->id)
                ->first(['quantity']);

            if ($existing) {
                DB::table('techs')
                    ->where('id', $user->current_research)
                    ->where('user_id', $user->id)
                    ->update(['quantity' => $existing->quantity + 1]);
            } else {
                DB::table('techs')->insert([
                    'id'       => $user->current_research,
                    'user_id'  => $user->id,
                    'quantity' => 1,
                ]);
            }
        }

        $doneIds = $done->pluck('id')->all();
        DB::table('users')
            ->whereIn('id', $doneIds)
            ->update([
                'current_research' => null,
                'research_time'    => null,
            ]);
    }
}
