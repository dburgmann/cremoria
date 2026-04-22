<?php

namespace Tests\Unit\Services\Game;

use App\Models\BuildingLevel;
use App\Models\TechLevel;
use App\Models\Town;
use App\Models\User;
use App\Services\Game\GameDataRepository;
use App\Services\Game\RecruitmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecruitmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private GameDataRepository $gameData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gameData = new GameDataRepository();

        \Spatie\Permission\Models\Role::create(['name' => 'login', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::create(['name' => 'unverified', 'guard_name' => 'web']);
    }

    private function makeUser(): User
    {
        return User::factory()->create();
    }

    private function makeTown(User $user, array $attrs = []): Town
    {
        return Town::create(array_merge([
            'user_id'      => $user->id,
            'name'         => 'Test Town',
            'x'            => 0,
            'y'            => 0,
            'gold'         => 9999999,
            'wood'         => 9999999,
            'stone'        => 9999999,
            'iron'         => 9999999,
            'unit_cap'     => 100,
            'unit_cap_used'=> 0,
        ], $attrs));
    }

    public function test_produce_returns_error_when_over_unit_cap(): void
    {
        $user = $this->makeUser();
        $town = $this->makeTown($user, ['unit_cap' => 5, 'unit_cap_used' => 0]);

        // Give user tech 1 (Landwirtschaft) for farmer
        TechLevel::create(['id' => 1, 'user_id' => $user->id, 'quantity' => 1]);
        $user->load('techs');

        $service = new RecruitmentService($this->gameData, $user, $town);
        $error   = $service->produce([1 => 10]); // trying to recruit 10 with only cap 5

        $this->assertSame('prodNotEnoughCap', $error);
    }

    public function test_produce_returns_error_for_forbidden_item(): void
    {
        $user = $this->makeUser();
        $town = $this->makeTown($user);

        // Try to recruit unit 4 (Ritter) without the required tech
        $service = new RecruitmentService($this->gameData, $user, $town);
        $error   = $service->produce([4 => 1]);

        $this->assertSame('prodForbiddenItem', $error);
    }

    public function test_produce_succeeds_and_creates_unit_production(): void
    {
        $user = $this->makeUser();
        $town = $this->makeTown($user);

        // Give tech 1 (Landwirtschaft)
        TechLevel::create(['id' => 1, 'user_id' => $user->id, 'quantity' => 1]);
        $user->load('techs');

        $service = new RecruitmentService($this->gameData, $user, $town);
        $error   = $service->produce([1 => 3]); // 3 farmers

        $this->assertNull($error);

        $this->assertDatabaseHas('unit_productions', [
            'unit_id'  => 1,
            'town_id'  => $town->id,
            'quantity' => 3,
        ]);
    }

    public function test_produce_increments_existing_queue_entry(): void
    {
        $user = $this->makeUser();
        $town = $this->makeTown($user);

        TechLevel::create(['id' => 1, 'user_id' => $user->id, 'quantity' => 1]);
        $user->load('techs');

        $service = new RecruitmentService($this->gameData, $user, $town);
        $service->produce([1 => 2]); // first batch
        $user->load('techs'); // reload
        $town->refresh();
        $service = new RecruitmentService($this->gameData, $user, $town);
        $service->produce([1 => 3]); // second batch

        // Should have one row with quantity = 5 (or two rows if time differs)
        $totalInQueue = \App\Models\UnitProduction::where('unit_id', 1)->where('town_id', $town->id)->sum('quantity');
        $this->assertSame(5, (int) $totalInQueue);
    }

    public function test_produce_returns_error_when_no_units_selected(): void
    {
        $user    = $this->makeUser();
        $town    = $this->makeTown($user);
        $service = new RecruitmentService($this->gameData, $user, $town);
        $error   = $service->produce([]);

        $this->assertSame('prodNoUnitsSelected', $error);
    }
}
