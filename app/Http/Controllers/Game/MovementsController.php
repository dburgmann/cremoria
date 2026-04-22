<?php

namespace App\Http\Controllers\Game;

use App\Http\Requests\MovementRequest;
use App\Models\Movement;
use App\Models\Town;
use App\Models\Troop;
use App\Models\UnitStock;
use App\Services\Game\GameDataRepository;
use App\Services\Game\GameFormulas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MovementsController extends GameController
{
    public function __construct(private GameDataRepository $gameData)
    {
        parent::__construct();
    }

    public function index(): View
    {
        $units = $this->activeTown->units->map(function ($stock) {
            $data = $this->gameData->unit((int) $stock->unit_id);
            return array_merge($data ?? [], ['quantity' => $stock->quantity, 'unit_id' => $stock->unit_id]);
        });

        $movements = Movement::where('owner', $this->user->id)
            ->with('troops')
            ->get();

        return view('game.movements', [
            'units'     => $units,
            'movements' => $movements,
            'town'      => $this->activeTown,
            'mapSize'   => (int) config('game.game.map_size', 100),
            'actionTypes' => config('game.game.movement_types_view', []),
        ]);
    }

    public function generate(MovementRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $destX  = (int) $validated['dest_x'];
        $destY  = (int) $validated['dest_y'];
        $action = (int) $validated['action'];
        $units  = array_filter((array) ($validated['units'] ?? []), fn ($q) => (int) $q > 0);
        $gold   = (int) ($validated['gold']  ?? 0);
        $wood   = (int) ($validated['wood']  ?? 0);
        $stone  = (int) ($validated['stone'] ?? 0);
        $iron   = (int) ($validated['iron']  ?? 0);

        // Validate: some units must be selected
        if (empty($units)) {
            return redirect()->route('game.movements')
                ->withErrors(['movements' => __('game.errors.movNoUnits')]);
        }

        // Validate: destination town exists
        $destTown = Town::where('x', $destX)->where('y', $destY)->first();
        if ($destTown === null) {
            return redirect()->route('game.movements')
                ->withErrors(['movements' => __('game.errors.movInvalidCoord')]);
        }

        // Validate: movement cap
        if ($this->activeTown->mov_cap_used >= $this->activeTown->mov_cap) {
            return redirect()->route('game.movements')
                ->withErrors(['movements' => __('game.errors.movNotEnoughCap')]);
        }

        // Validate: unit ownership and availability
        foreach ($units as $unitId => $qty) {
            $stock = $this->activeTown->units->firstWhere('unit_id', (int) $unitId);
            if ($stock === null || $stock->quantity < (int) $qty) {
                return redirect()->route('game.movements')
                    ->withErrors(['movements' => __('game.errors.movNotEnoughUnits')]);
            }
        }

        // Validate: cargo capacity
        $totalCargo   = $gold + $wood + $stone + $iron;
        $armyCapacity = $this->calculateArmyCapacity($units);

        if ($totalCargo > $armyCapacity) {
            return redirect()->route('game.movements')
                ->withErrors(['movements' => __('game.errors.movNotEnoughCapacity')]);
        }

        // Validate: town has enough resources
        if ($this->activeTown->gold < $gold || $this->activeTown->wood < $wood ||
            $this->activeTown->stone < $stone || $this->activeTown->iron < $iron) {
            return redirect()->route('game.movements')
                ->withErrors(['movements' => __('game.errors.movNotEnoughRes')]);
        }

        // Commit
        DB::transaction(function () use ($units, $destX, $destY, $action, $gold, $wood, $stone, $iron) {
            $duration = $this->calculateMovementDuration($units, $destX, $destY);

            $movement = Movement::create([
                'owner'    => $this->user->id,
                'start_x'  => $this->activeTown->x,
                'start_y'  => $this->activeTown->y,
                'dest_x'   => $destX,
                'dest_y'   => $destY,
                'action'   => $action,
                'gold'     => $gold,
                'wood'     => $wood,
                'stone'    => $stone,
                'iron'     => $iron,
                'duration' => $duration,
                'arrival'  => $duration,
            ]);

            foreach ($units as $unitId => $qty) {
                $qty = (int) $qty;
                $unitId = (int) $unitId;

                Troop::create([
                    'movement_id' => $movement->id,
                    'unit_id'     => $unitId,
                    'quantity'    => $qty,
                ]);

                $stock = UnitStock::where('unit_id', $unitId)
                    ->where('town_id', $this->activeTown->id)
                    ->first();

                if ($stock !== null) {
                    if ($stock->quantity <= $qty) {
                        $stock->delete();
                    } else {
                        $stock->decrement('quantity', $qty);
                    }
                }
            }

            $this->activeTown->decrement('gold', $gold);
            $this->activeTown->decrement('wood', $wood);
            $this->activeTown->decrement('stone', $stone);
            $this->activeTown->decrement('iron', $iron);
            $this->activeTown->increment('mov_cap_used');
        });

        return redirect()->route('game.movements')
            ->with('success', 'Armee wurde ausgesandt.');
    }

    private function calculateArmyCapacity(array $units): int
    {
        $cap = 0;
        foreach ($units as $unitId => $qty) {
            $data = $this->gameData->unit((int) $unitId);
            if ($data !== null) {
                $cap += (int) $data['cp'] * (int) $qty;
            }
        }
        return $cap;
    }

    private function calculateMovementDuration(array $units, int $destX, int $destY): int
    {
        $minSpeed = PHP_INT_MAX;
        foreach ($units as $unitId => $qty) {
            $data = $this->gameData->unit((int) $unitId);
            if ($data !== null) {
                $minSpeed = min($minSpeed, (int) $data['sp']);
            }
        }

        if ($minSpeed === PHP_INT_MAX || $minSpeed <= 0) {
            $minSpeed = 1;
        }

        $distX = $this->activeTown->x - $destX;
        $distY = $this->activeTown->y - $destY;
        $dist  = sqrt($distX ** 2 + $distY ** 2);

        return GameFormulas::movementDuration($dist, $minSpeed);
    }
}
