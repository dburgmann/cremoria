<?php

namespace App\Console\Commands;

use App\Models\Message;
use App\Models\Movement;
use App\Models\Town;
use App\Models\User;
use App\Services\Game\AttackerArmy;
use App\Services\Game\BattleReportService;
use App\Services\Game\BattleService;
use App\Services\Game\DefenderArmy;
use App\Services\Game\EspionageService;
use App\Services\Game\GameDataRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Movement tick: decrements arrival timers and processes completed movements.
 *
 * Replaces the Kohana TickMovement_Controller. All raw SQL has been replaced
 * with parameterized Eloquent/query-builder calls.
 */
class TickMovement extends Command
{
    protected $signature   = 'game:tick-movement';
    protected $description = 'Run the movement tick: move armies, process arrivals';

    private GameDataRepository $gameData;

    public function __construct(GameDataRepository $gameData)
    {
        parent::__construct();
        $this->gameData = $gameData;
    }

    public function handle(): int
    {
        DB::transaction(function () {
            $this->updateMovements();
            $this->handleMovements();
        });

        $this->info('Movement tick completed.');
        return Command::SUCCESS;
    }

    /** Decrement arrival counter for every movement. */
    private function updateMovements(): void
    {
        DB::table('movements')->update([
            'arrival' => DB::raw('arrival - 1'),
        ]);
    }

    /** Process all movements that have arrived (arrival == 0). */
    private function handleMovements(): void
    {
        $movementTypes = config('game.game.movement_types', []);

        // Eager-load troops to avoid N+1
        $arrivedMovements = Movement::with('troops')
            ->where('arrival', 0)
            ->get();

        $deleteMovIds = [];

        foreach ($arrivedMovements as $mov) {
            $actionName = $movementTypes[$mov->action] ?? null;

            switch ($actionName) {
                case 'Transfer':
                    $this->handleTransfer($mov);
                    $deleteMovIds[] = $mov->id;
                    break;

                case 'Transport':
                    $this->handleTransport($mov);
                    $deleteMovIds[] = $mov->id;
                    break;

                case 'Attack':
                    $this->handleAttack($mov);
                    $deleteMovIds[] = $mov->id;
                    break;

                case 'Espionage':
                    $this->handleEspionage($mov);
                    $deleteMovIds[] = $mov->id;
                    break;

                case 'Return':
                    $this->handleReturn($mov);
                    $deleteMovIds[] = $mov->id;
                    break;
            }
        }

        if (!empty($deleteMovIds)) {
            Movement::whereIn('id', $deleteMovIds)->delete();
        }
    }

    /** Transfer: deliver cargo and troops to destination town. */
    private function handleTransfer(Movement $mov): void
    {
        $destTown = Town::where('x', $mov->dest_x)->where('y', $mov->dest_y)->first();

        if ($destTown === null) {
            $this->warn("Transfer to [{$mov->dest_x},{$mov->dest_y}]: no town found, movement discarded.");
            return;
        }

        $this->deliverCargo($destTown, $mov);
        $this->deliverTroops($destTown, $mov);
    }

    /** Transport: deliver cargo, create return movement. */
    private function handleTransport(Movement $mov): void
    {
        $destTown = Town::where('x', $mov->dest_x)->where('y', $mov->dest_y)->first();

        if ($destTown !== null) {
            $this->deliverCargo($destTown, $mov);
        }

        $this->makeReturnMovement($mov, 0, 0, 0, 0);
    }

    /**
     * Attack: run combat, pillage if attacker won, create return movement.
     *
     * Bug fix: original code accessed $res[0]->user_id without checking if $res was empty.
     */
    private function handleAttack(Movement $mov): void
    {
        $destTown = Town::where('x', $mov->dest_x)->where('y', $mov->dest_y)->first();

        if ($destTown === null) {
            $this->warn("Attack on [{$mov->dest_x},{$mov->dest_y}]: no town found, treating as empty-coord attack.");
            // No defenders — attacker wins, return troops
            $this->makeReturnMovement($mov, 0, 0, 0, 0);
            return;
        }

        // Build armies
        $attUnitRows = $mov->troops->map(fn ($t) => (object) ['id' => $t->unit_id, 'unit_id' => $t->unit_id, 'quantity' => $t->quantity]);
        $defUnitRows = $destTown->units->map(fn ($u) => (object) ['id' => $u->unit_id, 'unit_id' => $u->unit_id, 'quantity' => $u->quantity]);

        $attArmy = new AttackerArmy((int) $mov->owner, $attUnitRows, $this->gameData);
        $defArmy = new DefenderArmy((int) $destTown->user_id, $defUnitRows, $this->gameData);
        $battle  = new BattleService($attArmy, $defArmy);

        $report = new BattleReportService($battle, $attArmy, $defArmy);
        $report->sendReport($attUnitRows, $defUnitRows);

        $battle->startBattle();

        $bootyGold = $bootyWood = $bootyStone = $bootyIron = 0;

        if ($battle->getWinner() === BattleService::ATTACKER_WINS) {
            $battle->pillage($destTown->id);
            $booty = $battle->getBooty();
            $bootyGold  = $booty['gold'];
            $bootyWood  = $booty['wood'];
            $bootyStone = $booty['stone'];
            $bootyIron  = $booty['iron'];
        }

        $report->sendReport($attUnitRows, $defUnitRows);

        $attArmy->save($mov->id);
        $defArmy->save($destTown->id);

        if (!$attArmy->isDefeated()) {
            $this->makeReturnMovement(
                $mov,
                $mov->gold + $bootyGold,
                $mov->wood + $bootyWood,
                $mov->stone + $bootyStone,
                $mov->iron + $bootyIron
            );
        }
    }

    /** Espionage: check success, send report, create return movement. */
    private function handleEspionage(Movement $mov): void
    {
        $destTown = Town::where('x', $mov->dest_x)->where('y', $mov->dest_y)->first();

        if ($destTown === null) {
            $this->warn("Espionage to [{$mov->dest_x},{$mov->dest_y}]: no town found.");
            return;
        }

        $doer   = User::with('towns.buildings')->find($mov->owner);
        $victim = User::find($destTown->user_id);

        if ($doer === null || $victim === null) {
            return;
        }

        $espionage = new EspionageService($doer, $victim, $destTown);
        $success   = $espionage->isSuccessful();
        $report    = $espionage->getReport($success);

        Message::create([
            'sender'   => 0,
            'receiver' => $mov->owner,
            'title'    => 'Spiobericht',
            'text'     => $report,
        ]);

        $this->makeReturnMovement($mov, 0, 0, 0, 0);
    }

    /** Return movement: same as transfer but also restores movCapUsed on arrival. */
    private function handleReturn(Movement $mov): void
    {
        $this->handleTransfer($mov);

        $destTown = Town::where('x', $mov->dest_x)->where('y', $mov->dest_y)->first();
        if ($destTown !== null) {
            $destTown->decrement('mov_cap_used');
        }
    }

    /**
     * Creates a return movement for the given movement's troops.
     */
    private function makeReturnMovement(
        Movement $mov,
        int $gold,
        int $wood,
        int $stone,
        int $iron
    ): void {
        $actions     = config('game.game.movement_types', []);
        $returnAction = (int) array_search('Return', $actions, true);

        $return = Movement::create([
            'owner'    => $mov->owner,
            'gold'     => $gold,
            'wood'     => $wood,
            'stone'    => $stone,
            'iron'     => $iron,
            'action'   => $returnAction,
            'start_x'  => $mov->dest_x,
            'start_y'  => $mov->dest_y,
            'dest_x'   => $mov->start_x,
            'dest_y'   => $mov->start_y,
            'duration' => $mov->duration,
            'arrival'  => $mov->duration,
        ]);

        // Reassign troops to the return movement
        DB::table('troops')
            ->where('movement_id', $mov->id)
            ->update(['movement_id' => $return->id]);
    }

    /** Delivers cargo (resources) from movement to a town. */
    private function deliverCargo(Town $town, Movement $mov): void
    {
        if ($mov->gold > 0 || $mov->wood > 0 || $mov->stone > 0 || $mov->iron > 0) {
            $town->increment('gold',  $mov->gold);
            $town->increment('wood',  $mov->wood);
            $town->increment('stone', $mov->stone);
            $town->increment('iron',  $mov->iron);
        }
    }

    /** Adds troops from movement into the destination town's unit stock. */
    private function deliverTroops(Town $town, Movement $mov): void
    {
        foreach ($mov->troops as $troop) {
            $existing = DB::table('units')
                ->where('unit_id', $troop->unit_id)
                ->where('town_id', $town->id)
                ->first(['quantity']);

            if ($existing) {
                DB::table('units')
                    ->where('unit_id', $troop->unit_id)
                    ->where('town_id', $town->id)
                    ->update(['quantity' => $existing->quantity + $troop->quantity]);
            } else {
                DB::table('units')->insert([
                    'unit_id'  => $troop->unit_id,
                    'town_id'  => $town->id,
                    'quantity' => $troop->quantity,
                ]);
            }
        }
    }
}
