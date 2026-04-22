<?php

namespace App\Services\Game;

/**
 * All game formula calculations in one explicit, testable place.
 * Replaces all eval() calls that read formula strings from Kohana config.
 *
 * Every method is pure (no side effects, no DB access).
 */
class GameFormulas
{
    /**
     * Gold income per tick for a given number of gold mine levels.
     * Formula: 50 * no * 1.05^no
     */
    public static function goldIncome(int $no): int
    {
        if ($no <= 0) {
            return 0;
        }
        return (int) ceil(50 * $no * pow(1.05, $no));
    }

    /**
     * Wood income per tick for a given number of wood building levels.
     * Iron smelters consume wood; subtract their contribution via ironToWood factor.
     * Formula: 50 * no * 1.05^no  (then caller subtracts iron consumption)
     */
    public static function woodIncome(int $no): int
    {
        if ($no <= 0) {
            return 0;
        }
        return (int) ceil(50 * $no * pow(1.05, $no));
    }

    /**
     * Stone income per tick for a given number of quarry levels.
     */
    public static function stoneIncome(int $no): int
    {
        if ($no <= 0) {
            return 0;
        }
        return (int) ceil(50 * $no * pow(1.05, $no));
    }

    /**
     * Maximum iron income per tick for a given number of smelter levels.
     */
    public static function ironIncomeMax(int $no): int
    {
        if ($no <= 0) {
            return 0;
        }
        return (int) ceil(50 * $no * pow(1.05, $no));
    }

    /**
     * Building cost multiplier based on existing quantity.
     * Formula: 1.25^(existingQty)  (quantity is 0-indexed: first building costs base)
     *
     * @param int $existingQty  number of this building type already built
     */
    public static function buildingCostMultiplier(int $existingQty): float
    {
        return pow(1.25, $existingQty);
    }

    /**
     * Unit cap for a given number of barracks levels.
     * Formula: 5 * no
     */
    public static function unitCap(int $no): int
    {
        return 5 * $no;
    }

    /**
     * Research time multiplier based on number of labs and tech level.
     * Formula: 0.95^labs * level
     *
     * @param int $labs   number of research lab buildings
     * @param int $level  current level (1-indexed: level=1 means first research)
     */
    public static function researchTimeMultiplier(int $labs, int $level): float
    {
        return pow(0.95, $labs) * $level;
    }

    /**
     * Research cost multiplier for repeatable techs.
     * Formula: 1.35^(currentLevel)  (currentLevel=0 for first research)
     *
     * @param int $currentLevel  times already researched (0 = first time)
     */
    public static function researchCostMultiplier(int $currentLevel): float
    {
        return pow(1.35, $currentLevel);
    }

    /**
     * Movement duration in ticks.
     * Formula: ceil((1 + dist) * 100 / speed)
     *
     * @param float $dist   Euclidean distance between start and destination
     * @param int   $speed  slowest unit speed in the army
     */
    public static function movementDuration(float $dist, int $speed): int
    {
        if ($speed <= 0) {
            return PHP_INT_MAX; // prevent division-by-zero
        }
        return (int) ceil((1 + $dist) * 100 / $speed);
    }

    /**
     * Damage dealt by a unit in one attack.
     * Formula: ap * 10
     *
     * @param int $ap  attack points of the unit
     */
    public static function unitDamage(int $ap): int
    {
        return $ap * 10;
    }

    /**
     * Espionage success chance (percentage 0–100).
     * Formula: 50 + (victimPower - doerPower) * 5
     * Clamped to [0, 100].
     *
     * @param int $doerPower    spy building level of the attacker
     * @param int $victimPower  spy defence building level of the defender
     */
    public static function espionageSuccessChance(int $doerPower, int $victimPower): int
    {
        $dif    = $victimPower - $doerPower;
        $chance = 50 + ($dif * 5);
        return max(0, min(100, (int) $chance));
    }

    /**
     * Points awarded for a production/construction based on resource cost.
     * Formula: ceil((gold * 2 + iron * 3 + wood + stone) / 100)
     */
    public static function points(int $gold, int $wood, int $stone, int $iron): int
    {
        return (int) ceil(($gold * 2 + $iron * 3 + $wood + $stone) / 100);
    }
}
