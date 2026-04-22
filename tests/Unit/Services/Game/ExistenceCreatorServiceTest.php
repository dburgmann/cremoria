<?php

namespace Tests\Unit\Services\Game;

use App\Models\Town;
use App\Models\User;
use App\Services\Game\ExistenceCreatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExistenceCreatorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Spatie\Permission\Models\Role::create(['name' => 'login', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::create(['name' => 'unverified', 'guard_name' => 'web']);
    }

    public function test_creates_town_for_new_user(): void
    {
        $user    = User::factory()->create();
        $service = new ExistenceCreatorService();

        $service->createNewExistence($user);

        $this->assertDatabaseHas('towns', [
            'user_id' => $user->id,
        ]);
    }

    public function test_town_has_starting_resources(): void
    {
        $user    = User::factory()->create();
        $service = new ExistenceCreatorService();
        $service->createNewExistence($user);

        $town = Town::where('user_id', $user->id)->first();

        $this->assertNotNull($town);
        $this->assertGreaterThan(0, $town->gold);
        $this->assertGreaterThan(0, $town->wood);
        $this->assertGreaterThan(0, $town->stone);
        $this->assertGreaterThan(0, $town->iron);
    }

    public function test_town_has_valid_coordinates(): void
    {
        $user    = User::factory()->create();
        $service = new ExistenceCreatorService();
        $service->createNewExistence($user);

        $town    = Town::where('user_id', $user->id)->first();
        $mapSize = (int) config('game.game.map_size', 100);

        $this->assertGreaterThanOrEqual(0, $town->x);
        $this->assertLessThan($mapSize, $town->x);
        $this->assertGreaterThanOrEqual(0, $town->y);
        $this->assertLessThan($mapSize, $town->y);
    }

    public function test_creates_town_with_unique_coordinates(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $service = new ExistenceCreatorService();
        $service->createNewExistence($user1);
        $service->createNewExistence($user2);

        $town1 = Town::where('user_id', $user1->id)->first();
        $town2 = Town::where('user_id', $user2->id)->first();

        // Coordinates must not overlap
        $this->assertFalse($town1->x === $town2->x && $town1->y === $town2->y);
    }

    public function test_town_name_contains_username(): void
    {
        $user    = User::factory()->create(['username' => 'TestPlayer']);
        $service = new ExistenceCreatorService();
        $service->createNewExistence($user);

        $town = Town::where('user_id', $user->id)->first();
        $this->assertStringContainsString('TestPlayer', $town->name);
    }
}
