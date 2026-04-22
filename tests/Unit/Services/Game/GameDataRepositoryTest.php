<?php

namespace Tests\Unit\Services\Game;

use App\Services\Game\GameDataRepository;
use Illuminate\Foundation\Testing\WithFaker;
use InvalidArgumentException;
use Tests\TestCase;

class GameDataRepositoryTest extends TestCase
{
    private GameDataRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new GameDataRepository();
    }

    // --- buildings ---

    public function test_building_returns_correct_data_for_known_id(): void
    {
        $building = $this->repo->building(1);
        $this->assertNotNull($building);
        $this->assertSame('Goldmine', $building['name']);
        $this->assertSame(1, $building['id']);
    }

    public function test_building_returns_null_for_unknown_id(): void
    {
        $this->assertNull($this->repo->building(9999));
    }

    public function test_building_or_fail_throws_for_unknown_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->repo->buildingOrFail(9999);
    }

    public function test_all_buildings_returns_array_keyed_by_id(): void
    {
        $buildings = $this->repo->allBuildings();
        $this->assertArrayHasKey(1, $buildings);
        $this->assertArrayHasKey(6, $buildings); // lab
    }

    // --- units ---

    public function test_unit_returns_correct_data_for_known_id(): void
    {
        $unit = $this->repo->unit(1);
        $this->assertNotNull($unit);
        $this->assertSame('Bauer', $unit['name']);
        $this->assertArrayHasKey('ap', $unit);
        $this->assertArrayHasKey('hp', $unit);
        $this->assertArrayHasKey('sp', $unit);
        $this->assertArrayHasKey('cp', $unit);
    }

    public function test_unit_returns_null_for_unknown_id(): void
    {
        $this->assertNull($this->repo->unit(9999));
    }

    public function test_unit_or_fail_throws_for_unknown_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->repo->unitOrFail(9999);
    }

    public function test_all_units_returns_multiple_entries(): void
    {
        $units = $this->repo->allUnits();
        $this->assertGreaterThan(5, count($units));
    }

    // --- techs ---

    public function test_tech_returns_correct_data_for_known_id(): void
    {
        $tech = $this->repo->tech(1);
        $this->assertNotNull($tech);
        $this->assertSame('Landwirtschaft', $tech['name']);
        $this->assertArrayHasKey('type', $tech);
    }

    public function test_tech_returns_null_for_unknown_id(): void
    {
        $this->assertNull($this->repo->tech(9999));
    }

    public function test_tech_or_fail_throws_for_unknown_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->repo->techOrFail(9999);
    }

    public function test_repeatable_tech_has_correct_type(): void
    {
        $spioTech = $this->repo->tech(25);
        $this->assertSame('repeatable', $spioTech['type']);
    }

    public function test_all_techs_includes_tech_25(): void
    {
        $techs = $this->repo->allTechs();
        $this->assertArrayHasKey(25, $techs);
    }
}
