<?php

namespace App\Services\Game;

use App\Models\Town;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Creates a new player's starting existence (town + starting resources).
 *
 * Bug fixes applied:
 * - Original setCoordinates had `$coord->y == y` (bare `y` undefined) → fixed to `$coord->y == $y`.
 * - Added 1000-attempt safety limit to prevent infinite loops on full maps.
 * - Registration now calls ExistenceCreatorService (was never called in old register.php).
 */
class ExistenceCreatorService
{
    private const MAX_COORDINATE_ATTEMPTS = 1000;

    public function createNewExistence(User $user): void
    {
        DB::transaction(function () use ($user) {
            $this->setupTown($user);
        });
    }

    private function setupTown(User $user): void
    {
        $town = new Town([
            'user_id' => $user->id,
            'name'    => $user->username . "'s Home",
            'gold'    => (int) config('game.game.init_gold', 300),
            'wood'    => (int) config('game.game.init_wood', 300),
            'stone'   => (int) config('game.game.init_stone', 300),
            'iron'    => (int) config('game.game.init_iron', 300),
        ]);

        ['x' => $x, 'y' => $y] = $this->findFreeCoordinates();

        $town->x = $x;
        $town->y = $y;
        $town->save();
    }

    /**
     * Finds a map coordinate that is not yet occupied by another town.
     * Bug fix: original code compared $coord->y == y (bare y, always falsy).
     *
     * @return array{x:int,y:int}
     * @throws RuntimeException  when no free coordinate is found within the attempt limit
     */
    private function findFreeCoordinates(): array
    {
        $mapSize = (int) config('game.game.map_size', 100);

        for ($attempt = 0; $attempt < self::MAX_COORDINATE_ATTEMPTS; $attempt++) {
            $x = random_int(0, $mapSize - 1);
            $y = random_int(0, $mapSize - 1);

            // Check for existing town at this coordinate (explicit DB query, no loop)
            $occupied = Town::where('x', $x)->where('y', $y)->exists();

            if (!$occupied) {
                return compact('x', 'y');
            }
        }

        throw new RuntimeException(
            "Could not find a free map coordinate after " . self::MAX_COORDINATE_ATTEMPTS . " attempts. Map may be full."
        );
    }
}
