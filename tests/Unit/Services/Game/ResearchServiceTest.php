<?php

namespace Tests\Unit\Services\Game;

use App\Models\BuildingLevel;
use App\Models\TechLevel;
use App\Models\Town;
use App\Models\User;
use App\Services\Game\GameDataRepository;
use App\Services\Game\ResearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResearchServiceTest extends TestCase
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
            'gold'    => 9999999,
            'wood'    => 9999999,
            'stone'   => 9999999,
            'iron'    => 9999999,
        ], $attrs));
    }

    private function addLab(User $user, Town $town, int $levels = 1): void
    {
        $labId = (int) config('game.items.research_building', 6);

        // Building 6 requires buildings 1, 2, 3 to exist — add those too
        foreach ([1, 2, 3] as $bldId) {
            BuildingLevel::create(['id' => $bldId, 'town_id' => $town->id, 'quantity' => 1]);
        }
        BuildingLevel::create(['id' => $labId, 'town_id' => $town->id, 'quantity' => $levels]);
        $town->load('buildings');
    }

    public function test_produce_returns_error_when_no_lab(): void
    {
        $user = $this->makeUser();
        $town = $this->makeTown($user);

        $service = new ResearchService($this->gameData, $user, $town);
        $error   = $service->produce(1);

        $this->assertSame('prodNoLab', $error);
    }

    public function test_produce_returns_error_when_already_researching(): void
    {
        $user = $this->makeUser(['current_research' => 1, 'research_time' => 5]);
        $town = $this->makeTown($user);
        $this->addLab($user, $town);

        $service = new ResearchService($this->gameData, $user, $town);
        $error   = $service->produce(1);

        $this->assertSame('prodAlreadyResearching', $error);
    }

    public function test_produce_returns_error_for_non_repeatable_already_owned(): void
    {
        $user = $this->makeUser();
        $town = $this->makeTown($user);
        $this->addLab($user, $town);

        // Give user tech 1 (Landwirtschaft - non-repeatable)
        TechLevel::create(['id' => 1, 'user_id' => $user->id, 'quantity' => 1]);
        $user->load('techs');

        $service = new ResearchService($this->gameData, $user, $town);
        $error   = $service->produce(1);

        $this->assertSame('prodForbiddenItem', $error);
    }

    public function test_produce_succeeds_for_repeatable_tech(): void
    {
        $user = $this->makeUser();
        $town = $this->makeTown($user);
        $this->addLab($user, $town);

        $service = new ResearchService($this->gameData, $user, $town);
        $error   = $service->produce(25); // Spionage — repeatable

        $this->assertNull($error);

        $user->refresh();
        $this->assertSame(25, $user->current_research);
        $this->assertNotNull($user->research_time);
    }

    public function test_produce_succeeds_for_new_tech_without_requirements(): void
    {
        $user = $this->makeUser();
        $town = $this->makeTown($user);
        $this->addLab($user, $town);

        $service = new ResearchService($this->gameData, $user, $town);
        $error   = $service->produce(1); // Landwirtschaft — no requirements

        $this->assertNull($error);
    }

    public function test_calculate_applies_time_reduction_with_labs(): void
    {
        $item = ['gold' => 1000, 'wood' => 1000, 'stone' => 1000, 'iron' => 0, 'time' => 10, 'type' => 'economy'];

        $with0Labs = ResearchService::calculate($item, 0, 0);
        $with5Labs = ResearchService::calculate($item, 5, 0);

        $this->assertGreaterThan($with5Labs['time'], $with0Labs['time']);
    }

    public function test_calculate_repeatable_cost_increases_with_level(): void
    {
        $item = ['gold' => 100, 'wood' => 100, 'stone' => 100, 'iron' => 0, 'time' => 5, 'type' => 'repeatable'];

        $level0 = ResearchService::calculate($item, 1, 0);
        $level1 = ResearchService::calculate($item, 1, 1);

        $this->assertGreaterThan($level0['gold'], $level1['gold']);
    }
}
