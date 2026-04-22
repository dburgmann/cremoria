<?php

namespace Tests\Unit\Services\Game;

use App\Models\BuildingLevel;
use App\Models\TechLevel;
use App\Models\Town;
use App\Models\User;
use App\Services\Game\ConstructionService;
use App\Services\Game\GameDataRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConstructionServiceTest extends TestCase
{
    use RefreshDatabase;

    private GameDataRepository $gameData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gameData = new GameDataRepository();

        // Seed required roles
        \Spatie\Permission\Models\Role::create(['name' => 'login', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::create(['name' => 'unverified', 'guard_name' => 'web']);
    }

    private function makeUser(array $attrs = []): User
    {
        return User::factory()->create($attrs);
    }

    private function makeTown(User $user, array $attrs = []): Town
    {
        return Town::create(array_merge([
            'user_id' => $user->id,
            'name'    => 'Test Town',
            'x'       => 0,
            'y'       => 0,
            'gold'    => 9999,
            'wood'    => 9999,
            'stone'   => 9999,
            'iron'    => 9999,
        ], $attrs));
    }

    public function test_produce_returns_error_when_already_building(): void
    {
        $user = $this->makeUser();
        $town = $this->makeTown($user, ['current_building' => 1, 'building_time' => 5]);

        $service = new ConstructionService($this->gameData, $user, $town);
        $error   = $service->produce(1);

        $this->assertSame('prodAlreadyBuilding', $error);
    }

    public function test_produce_returns_error_when_item_not_allowed(): void
    {
        $user = $this->makeUser();
        $town = $this->makeTown($user);

        // Building 4 (iron smelter) requires tech 9 — which user doesn't have
        $service = new ConstructionService($this->gameData, $user, $town);
        $error   = $service->produce(4);

        $this->assertSame('prodForbiddenItem', $error);
    }

    public function test_produce_returns_error_when_not_enough_resources(): void
    {
        $user = $this->makeUser();
        $town = $this->makeTown($user, ['gold' => 0, 'wood' => 0, 'stone' => 0, 'iron' => 0]);

        // Building 1 (Goldmine) has no requirements
        $service = new ConstructionService($this->gameData, $user, $town);
        $error   = $service->produce(1);

        $this->assertSame('prodNotEnoughRes', $error);
    }

    public function test_produce_succeeds_and_deducts_resources(): void
    {
        $user = $this->makeUser();
        $town = $this->makeTown($user, ['gold' => 9999, 'wood' => 9999, 'stone' => 9999]);

        $service = new ConstructionService($this->gameData, $user, $town);
        $error   = $service->produce(1); // Goldmine: gold=100, wood=75, stone=50

        $this->assertNull($error);

        $town->refresh();
        $this->assertSame(1, $town->current_building);
        $this->assertLessThan(9999, $town->gold);
        $this->assertLessThan(9999, $town->wood);
    }

    public function test_calculate_applies_cost_multiplier_for_existing_quantity(): void
    {
        $baseItem = ['gold' => 100, 'wood' => 75, 'stone' => 50, 'iron' => 0, 'time' => 0];

        $first  = ConstructionService::calculate($baseItem, 0);
        $second = ConstructionService::calculate($baseItem, 1);

        $this->assertGreaterThan($first['gold'], $second['gold']);
    }

    public function test_calculate_cost_formula_uses_correct_fields(): void
    {
        // Bug fix: was gold + wood + wood + wood instead of gold + wood + stone + iron
        $item = ['gold' => 100, 'wood' => 100, 'stone' => 100, 'iron' => 100, 'time' => 0];
        $calc = ConstructionService::calculate($item, 0); // multiplier = 1.0

        $divider = (int) config('game.game.building_time_divider', 1000);
        $sum     = 100 + 100 + 100 + 100; // gold + wood + stone + iron
        $expectedTime = (int) ceil(0 + ($sum / $divider));

        $this->assertSame($expectedTime, $calc['time']);
    }

    public function test_building_with_required_tech_unlocked(): void
    {
        $user = $this->makeUser();
        $town = $this->makeTown($user, ['gold' => 9999, 'wood' => 9999, 'stone' => 9999, 'iron' => 9999]);

        // Add tech 9 (Metallverarbeitung) which is required for iron smelter (building 4)
        TechLevel::create(['id' => 9, 'user_id' => $user->id, 'quantity' => 1]);

        $user->load('techs');
        $service = new ConstructionService($this->gameData, $user, $town);

        $this->assertArrayHasKey(4, $service->getAllowed());
    }
}
