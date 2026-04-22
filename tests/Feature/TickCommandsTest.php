<?php

namespace Tests\Feature;

use App\Models\BuildingLevel;
use App\Models\Movement;
use App\Models\TechLevel;
use App\Models\Town;
use App\Models\Troop;
use App\Models\UnitProduction;
use App\Models\UnitStock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TickCommandsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Spatie\Permission\Models\Role::create(['name' => 'login', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::create(['name' => 'unverified', 'guard_name' => 'web']);
    }

    private function makeUser(array $attrs = []): User
    {
        return User::factory()->create($attrs);
    }

    private function makeTown(User $user, array $attrs = []): Town
    {
        static $coordOffset = 0;
        $coordOffset++;
        return Town::create(array_merge([
            'user_id'  => $user->id,
            'name'     => 'Town',
            'x'        => $coordOffset,
            'y'        => $coordOffset,
            'gold'     => 100,
            'wood'     => 100,
            'stone'    => 100,
            'iron'     => 100,
            'gold_inc' => 10,
            'wood_inc' => 10,
            'stone_inc'=> 10,
            'iron_inc' => 5,
        ], $attrs));
    }

    // --- TickEconomy ---

    public function test_tick_economy_adds_resource_income(): void
    {
        $user = $this->makeUser();
        $town = $this->makeTown($user, ['gold' => 100, 'gold_inc' => 10]);

        $this->artisan('game:tick-economy')->assertSuccessful();

        $town->refresh();
        $this->assertSame(110, $town->gold);
    }

    public function test_tick_economy_decrements_building_time(): void
    {
        $user = $this->makeUser();
        $town = $this->makeTown($user, ['current_building' => 1, 'building_time' => 3]);

        $this->artisan('game:tick-economy')->assertSuccessful();

        $town->refresh();
        $this->assertSame(2, $town->building_time);
    }

    public function test_tick_economy_completes_building(): void
    {
        $user = $this->makeUser();
        $town = $this->makeTown($user, ['current_building' => 1, 'building_time' => 1]);

        $this->artisan('game:tick-economy')->assertSuccessful();

        $town->refresh();
        $this->assertNull($town->current_building);
        $this->assertNull($town->building_time);

        $this->assertDatabaseHas('buildings', [
            'id'      => 1,
            'town_id' => $town->id,
        ]);
    }

    public function test_tick_economy_completes_unit_production(): void
    {
        $user = $this->makeUser();
        $town = $this->makeTown($user, ['unit_cap_used' => 3]);

        UnitProduction::create([
            'unit_id'  => 1,
            'town_id'  => $town->id,
            'quantity' => 3,
            'time'     => 1,
        ]);

        $this->artisan('game:tick-economy')->assertSuccessful();

        $this->assertDatabaseHas('units', [
            'unit_id' => 1,
            'town_id' => $town->id,
        ]);

        // Production row should be deleted
        $this->assertDatabaseMissing('unit_productions', [
            'unit_id' => 1,
            'town_id' => $town->id,
        ]);
    }

    public function test_tick_economy_completes_research(): void
    {
        $user = $this->makeUser(['current_research' => 1, 'research_time' => 1]);
        $this->makeTown($user);

        $this->artisan('game:tick-economy')->assertSuccessful();

        $user->refresh();
        $this->assertNull($user->current_research);
        $this->assertNull($user->research_time);

        $this->assertDatabaseHas('techs', [
            'id'      => 1,
            'user_id' => $user->id,
        ]);
    }

    public function test_tick_economy_does_not_complete_building_with_time_remaining(): void
    {
        $user = $this->makeUser();
        $town = $this->makeTown($user, ['current_building' => 1, 'building_time' => 5]);

        $this->artisan('game:tick-economy')->assertSuccessful();

        $town->refresh();
        $this->assertNotNull($town->current_building);
    }

    public function test_tick_economy_idempotent_with_no_active_productions(): void
    {
        $user = $this->makeUser();
        $this->makeTown($user);

        $this->artisan('game:tick-economy')->assertSuccessful();
        $this->artisan('game:tick-economy')->assertSuccessful();
        // Should not throw
        $this->assertTrue(true);
    }

    // --- TickScore ---

    public function test_tick_score_updates_total_pts(): void
    {
        $user = $this->makeUser(['research_pts' => 50]);
        Town::create([
            'user_id'      => $user->id,
            'name'         => 'Town',
            'x'            => 99,
            'y'            => 99,
            'building_pts' => 100,
        ]);

        $this->artisan('game:tick-score')->assertSuccessful();

        $user->refresh();
        $this->assertSame(100, $user->building_pts);
        $this->assertSame(150, $user->total_pts); // 50 research + 100 building
    }

    // --- TickMovement ---

    public function test_tick_movement_decrements_arrival(): void
    {
        $user     = $this->makeUser();
        $movement = Movement::create([
            'owner'    => $user->id,
            'start_x'  => 0,
            'start_y'  => 0,
            'dest_x'   => 5,
            'dest_y'   => 5,
            'action'   => 0,
            'duration' => 5,
            'arrival'  => 5,
        ]);

        $this->artisan('game:tick-movement')->assertSuccessful();

        $movement->refresh();
        $this->assertSame(4, $movement->arrival);
    }

    public function test_tick_movement_processes_transfer_arrival(): void
    {
        $user  = $this->makeUser();
        $srcTown = $this->makeTown($user, ['x' => 10, 'y' => 10]);
        $destTown = Town::create([
            'user_id' => $user->id,
            'name'    => 'Dest',
            'x'       => 20,
            'y'       => 20,
            'gold'    => 50,
        ]);

        $movement = Movement::create([
            'owner'    => $user->id,
            'start_x'  => 10,
            'start_y'  => 10,
            'dest_x'   => 20,
            'dest_y'   => 20,
            'action'   => 0, // Transfer
            'gold'     => 100,
            'duration' => 1,
            'arrival'  => 1,
        ]);

        $this->artisan('game:tick-movement')->assertSuccessful();

        $destTown->refresh();
        $this->assertSame(150, $destTown->gold); // 50 + 100 transferred

        // Movement should be deleted after processing
        $this->assertDatabaseMissing('movements', ['id' => $movement->id]);
    }

    public function test_tick_movement_attack_empty_coords_does_not_crash(): void
    {
        $user = $this->makeUser();
        $this->makeTown($user, ['x' => 5, 'y' => 5]);

        $movement = Movement::create([
            'owner'    => $user->id,
            'start_x'  => 5,
            'start_y'  => 5,
            'dest_x'   => 50,
            'dest_y'   => 50, // No town here
            'action'   => 2,  // Attack
            'duration' => 1,
            'arrival'  => 1,
        ]);

        // Should not throw even with no town at destination
        $this->artisan('game:tick-movement')->assertSuccessful();
    }
}
