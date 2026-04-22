<?php

namespace App\Services\Game;

use stdClass;

/**
 * Abstract army class used in battle calculations.
 * Units are expanded individually so each unit instance can track its own HP.
 */
abstract class Army
{
    /** @var int Owner user ID */
    protected int $owner;

    /** @var int Current battle round */
    protected int $round = 0;

    /** @var array<int,int> Damage dealt per round, indexed by round number */
    protected array $damageDealt = [];

    /** @var array<int,stdClass> Expanded unit array — each slot is one unit instance */
    protected array $units = [];

    /** @var int Total unit count at the start of battle */
    protected int $initArmySize = 0;

    /** @var array<int,stdClass> Casualties keyed by unit type ID */
    protected array $casualties = [];

    /** @var int Total units killed so far */
    protected int $noCasualties = 0;

    /** @var int Index of the next unit to attack in the current round */
    protected int $nextAttacker = 0;

    /** @var bool True when all living units have attacked this round */
    protected bool $roundFinished = true;

    /** @var bool True when all units in this army are dead */
    protected bool $defeated = false;

    /**
     * @param int                     $owner   User ID of the army owner
     * @param iterable<object>        $units   Collection of unit rows with unit_id and quantity
     * @param GameDataRepository      $gameData
     */
    public function __construct(int $owner, iterable $units, GameDataRepository $gameData)
    {
        $this->owner = $owner;

        foreach ($units as $unitRow) {
            $unitId   = (int) ($unitRow->unit_id ?? $unitRow->id);
            $quantity = (int) $unitRow->quantity;
            $data     = $gameData->unit($unitId);

            if ($data === null) {
                continue; // unknown unit type — skip silently
            }

            for ($i = 0; $i < $quantity; $i++) {
                $instance       = new stdClass();
                $instance->id   = $unitId;
                $instance->name = $data['name'];
                $instance->ap   = (int) $data['ap'];
                $instance->hp   = (int) $data['hp'];
                $instance->cp   = (int) $data['cp'];
                $instance->sp   = (int) $data['sp'];
                $this->units[]  = $instance;
                $this->initArmySize++;
            }
        }

        // Sort ascending by speed so slowest units attack first
        usort($this->units, static fn ($a, $b) => $a->sp <=> $b->sp);
    }

    /**
     * Determines the attacking unit and returns the damage it deals.
     * Returns 0 if the round is already finished or the army is defeated.
     */
    public function attack(): int
    {
        if ($this->roundFinished || $this->defeated) {
            return 0;
        }

        // Advance past dead slots
        while ($this->nextAttacker < $this->initArmySize
            && !isset($this->units[$this->nextAttacker])) {
            $this->nextAttacker++;
        }

        if ($this->nextAttacker >= $this->initArmySize || !isset($this->units[$this->nextAttacker])) {
            $this->roundFinished = true;
            return 0;
        }

        $ap     = $this->units[$this->nextAttacker]->ap;
        $damage = GameFormulas::unitDamage($ap);

        $this->nextAttacker++;

        if ($this->nextAttacker >= $this->initArmySize) {
            $this->roundFinished = true;
        }

        $this->damageDealt[$this->round] = ($this->damageDealt[$this->round] ?? 0) + $damage;

        return $damage;
    }

    /**
     * Applies damage to a random living unit.
     * Removes the unit if its HP drops to or below zero.
     */
    public function defend(int $damage): void
    {
        if ($damage <= 0 || $this->defeated) {
            return;
        }

        // No units were ever in this army — mark as defeated immediately
        if ($this->initArmySize === 0) {
            $this->defeated      = true;
            $this->roundFinished = true;
            return;
        }

        // Pick a random target slot
        $target = mt_rand(0, $this->initArmySize - 1);

        // If the target slot is empty, scan backwards for a living unit
        if (!isset($this->units[$target])) {
            $target = $this->initArmySize - 1;
            while ($target >= 0 && !isset($this->units[$target])) {
                $target--;
            }

            if ($target < 0 || !isset($this->units[$target])) {
                $this->defeated      = true;
                $this->roundFinished = true;
                return;
            }
        }

        $this->units[$target]->hp -= $damage;

        if ($this->units[$target]->hp <= 0) {
            $uid = $this->units[$target]->id;

            if (!isset($this->casualties[$uid])) {
                $cas           = new stdClass();
                $cas->id       = $uid;
                $cas->name     = $this->units[$target]->name;
                $cas->quantity = 0;
                $this->casualties[$uid] = $cas;
            }

            $this->casualties[$uid]->quantity++;
            unset($this->units[$target]);
            $this->noCasualties++;
        }

        if ($this->noCasualties >= $this->initArmySize) {
            $this->defeated      = true;
            $this->roundFinished = true;
        }
    }

    /**
     * Returns the total HP of all living units (used for win/loss determination).
     * Returns at least 1 to avoid division-by-zero in the Battle class.
     */
    public function getPower(): int
    {
        $power = 0;
        foreach ($this->units as $unit) {
            $power += $unit->hp;
        }
        return max(1, $power);
    }

    /**
     * Returns the total cargo capacity of all living units.
     */
    public function getCargoCapacity(): int
    {
        $cap = 0;
        foreach ($this->units as $unit) {
            $cap += $unit->cp;
        }
        return $cap;
    }

    /**
     * Resets per-round counters to begin the next round.
     */
    public function startNewRound(): void
    {
        $this->roundFinished           = false;
        $this->nextAttacker            = 0;
        $this->round++;
        $this->damageDealt[$this->round] = 0;
    }

    /** @return int Owner user ID */
    public function getOwner(): int
    {
        return $this->owner;
    }

    /** @return array<int,stdClass> Living unit instances */
    public function getUnits(): array
    {
        return $this->units;
    }

    /** @return array<int,stdClass> Casualties keyed by unit type ID */
    public function getCasualties(): array
    {
        return $this->casualties;
    }

    /** @return array<int,int> Damage dealt per round */
    public function getDamage(): array
    {
        return $this->damageDealt;
    }

    public function roundFinished(): bool
    {
        return $this->roundFinished;
    }

    public function isDefeated(): bool
    {
        return $this->defeated;
    }

    /**
     * Persist battle results to the database.
     * Subclasses implement this for attacker (troops) and defender (units) tables.
     */
    abstract public function save(int $contextId): void;
}
