<?php

namespace App\Services\Game;

use App\Models\Town;

/**
 * Orchestrates a complete battle between an attacker and a defender army.
 * Replaces the Kohana Battle class.
 */
class BattleService
{
    const ATTACKER_WINS = 0;
    const DEFENDER_WINS = 1;
    const DRAW          = 2;

    private AttackerArmy $attacker;
    private DefenderArmy $defender;
    private int $rounds;
    private int $winner = self::DRAW;

    /** @var array{gold:int,wood:int,stone:int,iron:int} */
    private array $booty = ['gold' => 0, 'wood' => 0, 'stone' => 0, 'iron' => 0];

    public function __construct(AttackerArmy $attacker, DefenderArmy $defender)
    {
        $this->attacker = $attacker;
        $this->defender = $defender;
        $this->rounds   = (int) config('game.game.battle_rounds', 3);
    }

    /**
     * Run the full battle.
     */
    public function startBattle(): void
    {
        $startPowerAtt = $this->attacker->getPower();
        $startPowerDef = $this->defender->getPower();

        for ($i = 1; $i <= $this->rounds; $i++) {
            if ($this->attacker->isDefeated() || $this->defender->isDefeated()) {
                break;
            }
            $this->fightRound();
        }

        $endPowerAtt = $this->attacker->getPower();
        $endPowerDef = $this->defender->getPower();

        $this->determineWinner($startPowerAtt, $endPowerAtt, $startPowerDef, $endPowerDef);
    }

    /**
     * Executes one round of combat: every living unit attacks and defends.
     */
    private function fightRound(): void
    {
        $this->attacker->startNewRound();
        $this->defender->startNewRound();

        while (!($this->attacker->roundFinished() && $this->defender->roundFinished())) {
            if (!$this->attacker->roundFinished()) {
                $this->defender->defend($this->attacker->attack());
            }

            if (!$this->defender->roundFinished()) {
                $this->attacker->defend($this->defender->attack());
            }
        }
    }

    /**
     * Determines the winner from the percentual HP loss on the defending side.
     * If startPowerDef == 1 (no defenders), attacker wins outright.
     */
    private function determineWinner(
        int $startPowerAtt,
        int $endPowerAtt,
        int $startPowerDef,
        int $endPowerDef
    ): void {
        // No defenders at all → instant attacker win
        if ($startPowerDef <= 1) {
            $this->winner = self::ATTACKER_WINS;
            return;
        }

        $difD = ($startPowerDef - $endPowerDef) / $startPowerDef;

        if ($difD > 0.5) {
            $this->winner = self::ATTACKER_WINS;
        } elseif ($difD < 0.5) {
            $this->winner = self::DEFENDER_WINS;
        } else {
            $this->winner = self::DRAW;
        }
    }

    /**
     * Calculates and deducts pillage resources from the defending town.
     * Random allocation of attacker carry capacity across four resource types.
     *
     * @param int $townId  ID of the defending town
     */
    public function pillage(int $townId): void
    {
        $town = Town::findOrFail($townId);

        $cap       = $this->attacker->getCargoCapacity();
        $ironPart  = mt_rand(0, $cap);
        $goldPart  = mt_rand(0, $cap - $ironPart);
        $remainder = $cap - $ironPart - $goldPart;
        $woodPart  = (int) ceil($remainder * 0.5);
        $stonePart = (int) ceil($remainder * 0.5);

        // Iron
        if ($town->iron < $ironPart) {
            $iron = $town->iron;
            $goldPart += ($ironPart - $iron);
            $town->iron = 0;
        } else {
            $iron = $ironPart;
            $town->iron -= $ironPart;
        }

        // Gold
        if ($town->gold < $goldPart) {
            $gold = $town->gold;
            $stonePart += ($goldPart - $gold);
            $town->gold = 0;
        } else {
            $gold = $goldPart;
            $town->gold -= $goldPart;
        }

        // Stone
        if ($town->stone < $stonePart) {
            $stone = $town->stone;
            $woodPart += ($stonePart - $stone);
            $town->stone = 0;
        } else {
            $stone = $stonePart;
            $town->stone -= $stonePart;
        }

        // Wood
        if ($town->wood < $woodPart) {
            $wood = $town->wood;
            $town->wood = 0;
        } else {
            $wood = $woodPart;
            $town->wood -= $woodPart;
        }

        $this->booty = compact('gold', 'wood', 'stone', 'iron');
        $town->save();
    }

    public function getRounds(): int
    {
        return $this->rounds;
    }

    public function getWinner(): int
    {
        return $this->winner;
    }

    /** @return array{gold:int,wood:int,stone:int,iron:int} */
    public function getBooty(): array
    {
        return $this->booty;
    }

    public function getAttacker(): AttackerArmy
    {
        return $this->attacker;
    }

    public function getDefender(): DefenderArmy
    {
        return $this->defender;
    }
}
