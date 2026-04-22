<?php

namespace Tests\Unit\Services\Game;

use App\Services\Game\GameFormulas;
use PHPUnit\Framework\TestCase;

class GameFormulasTest extends TestCase
{
    // --- goldIncome ---

    public function test_gold_income_zero_buildings_returns_zero(): void
    {
        $this->assertSame(0, GameFormulas::goldIncome(0));
    }

    public function test_gold_income_negative_buildings_returns_zero(): void
    {
        $this->assertSame(0, GameFormulas::goldIncome(-5));
    }

    public function test_gold_income_level_one(): void
    {
        // 50 * 1 * 1.05^1 = 52.5 → ceil = 53
        $this->assertSame(53, GameFormulas::goldIncome(1));
    }

    public function test_gold_income_level_five(): void
    {
        // 50 * 5 * 1.05^5 ≈ 319.07 → ceil = 320
        $expected = (int) ceil(50 * 5 * pow(1.05, 5));
        $this->assertSame($expected, GameFormulas::goldIncome(5));
    }

    public function test_gold_income_increases_with_level(): void
    {
        $this->assertGreaterThan(GameFormulas::goldIncome(1), GameFormulas::goldIncome(2));
    }

    // --- woodIncome / stoneIncome / ironIncomeMax follow same formula ---

    public function test_wood_income_matches_gold_income_formula(): void
    {
        $this->assertSame(GameFormulas::goldIncome(3), GameFormulas::woodIncome(3));
    }

    public function test_stone_income_matches_formula(): void
    {
        $this->assertSame(GameFormulas::goldIncome(4), GameFormulas::stoneIncome(4));
    }

    public function test_iron_income_max_matches_formula(): void
    {
        $this->assertSame(GameFormulas::goldIncome(2), GameFormulas::ironIncomeMax(2));
    }

    // --- buildingCostMultiplier ---

    public function test_building_cost_multiplier_zero_existing_is_one(): void
    {
        $this->assertEqualsWithDelta(1.0, GameFormulas::buildingCostMultiplier(0), 0.001);
    }

    public function test_building_cost_multiplier_one_existing(): void
    {
        $this->assertEqualsWithDelta(1.25, GameFormulas::buildingCostMultiplier(1), 0.001);
    }

    public function test_building_cost_multiplier_increases(): void
    {
        $this->assertGreaterThan(
            GameFormulas::buildingCostMultiplier(2),
            GameFormulas::buildingCostMultiplier(3)
        );
    }

    // --- unitCap ---

    public function test_unit_cap_is_five_per_level(): void
    {
        $this->assertSame(0, GameFormulas::unitCap(0));
        $this->assertSame(5, GameFormulas::unitCap(1));
        $this->assertSame(15, GameFormulas::unitCap(3));
    }

    // --- researchTimeMultiplier ---

    public function test_research_time_multiplier_no_labs_level_one(): void
    {
        // 0.95^0 * 1 = 1.0
        $this->assertEqualsWithDelta(1.0, GameFormulas::researchTimeMultiplier(0, 1), 0.001);
    }

    public function test_research_time_multiplier_with_labs_reduces_time(): void
    {
        $withLabs    = GameFormulas::researchTimeMultiplier(5, 1);
        $withoutLabs = GameFormulas::researchTimeMultiplier(0, 1);
        $this->assertLessThan($withoutLabs, $withLabs);
    }

    public function test_research_time_multiplier_higher_level_increases_time(): void
    {
        $level1 = GameFormulas::researchTimeMultiplier(3, 1);
        $level3 = GameFormulas::researchTimeMultiplier(3, 3);
        $this->assertGreaterThan($level1, $level3);
    }

    // --- researchCostMultiplier ---

    public function test_research_cost_multiplier_first_research_is_one(): void
    {
        $this->assertEqualsWithDelta(1.0, GameFormulas::researchCostMultiplier(0), 0.001);
    }

    public function test_research_cost_multiplier_increases_with_level(): void
    {
        $this->assertGreaterThan(
            GameFormulas::researchCostMultiplier(1),
            GameFormulas::researchCostMultiplier(2)
        );
    }

    // --- movementDuration ---

    public function test_movement_duration_zero_distance(): void
    {
        // (1+0)*100/100 = 1
        $this->assertSame(1, GameFormulas::movementDuration(0.0, 100));
    }

    public function test_movement_duration_with_distance(): void
    {
        // dist=3, speed=100 → ceil((1+3)*100/100) = 4
        $this->assertSame(4, GameFormulas::movementDuration(3.0, 100));
    }

    public function test_movement_duration_zero_speed_returns_max_int(): void
    {
        $this->assertSame(PHP_INT_MAX, GameFormulas::movementDuration(5.0, 0));
    }

    public function test_movement_duration_slow_army_takes_longer(): void
    {
        $fast = GameFormulas::movementDuration(10.0, 200);
        $slow = GameFormulas::movementDuration(10.0, 50);
        $this->assertGreaterThan($fast, $slow);
    }

    // --- unitDamage ---

    public function test_unit_damage_is_ap_times_ten(): void
    {
        $this->assertSame(50, GameFormulas::unitDamage(5));
        $this->assertSame(100, GameFormulas::unitDamage(10));
        $this->assertSame(0, GameFormulas::unitDamage(0));
    }

    // --- espionageSuccessChance ---

    public function test_espionage_success_equal_power_is_50_percent(): void
    {
        $this->assertSame(50, GameFormulas::espionageSuccessChance(5, 5));
    }

    public function test_espionage_success_higher_doer_power_increases_chance(): void
    {
        $lower  = GameFormulas::espionageSuccessChance(3, 5); // dif=2 → 60
        $higher = GameFormulas::espionageSuccessChance(1, 5); // dif=4 → 70
        $this->assertGreaterThan($lower, $higher);
    }

    public function test_espionage_success_clamped_to_100(): void
    {
        $this->assertSame(100, GameFormulas::espionageSuccessChance(0, 100));
    }

    public function test_espionage_success_clamped_to_zero(): void
    {
        $this->assertSame(0, GameFormulas::espionageSuccessChance(100, 0));
    }

    // --- points ---

    public function test_points_formula(): void
    {
        // (100*2 + 200*3 + 50 + 75) / 100 = (200+600+50+75)/100 = 925/100 = 9.25 → ceil = 10
        $this->assertSame(10, GameFormulas::points(100, 50, 75, 200));
    }

    public function test_points_zero_resources(): void
    {
        $this->assertSame(0, GameFormulas::points(0, 0, 0, 0));
    }

    public function test_points_increases_with_iron(): void
    {
        $withIron    = GameFormulas::points(100, 100, 100, 100);
        $withoutIron = GameFormulas::points(100, 100, 100, 0);
        $this->assertGreaterThan($withoutIron, $withIron);
    }
}
