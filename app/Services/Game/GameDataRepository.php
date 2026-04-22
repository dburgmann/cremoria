<?php

namespace App\Services\Game;

use InvalidArgumentException;

/**
 * Provides read-only access to static game data (buildings, units, techs).
 * Data is loaded from PHP config arrays (config/game/) and cached by opcache
 * after `php artisan config:cache` in production. No I/O at runtime.
 *
 * Registered as a singleton in AppServiceProvider.
 */
class GameDataRepository
{
    /**
     * Get a building definition by its ID.
     *
     * @return array<string,mixed>|null  null when the ID does not exist
     */
    public function building(int $id): ?array
    {
        return config("game.buildings.{$id}");
    }

    /**
     * Get all building definitions, keyed by building ID.
     *
     * @return array<int, array<string,mixed>>
     */
    public function allBuildings(): array
    {
        return config('game.buildings', []);
    }

    /**
     * Get a unit definition by its ID.
     *
     * @return array<string,mixed>|null
     */
    public function unit(int $id): ?array
    {
        return config("game.units.{$id}");
    }

    /**
     * Get all unit definitions, keyed by unit ID.
     *
     * @return array<int, array<string,mixed>>
     */
    public function allUnits(): array
    {
        return config('game.units', []);
    }

    /**
     * Get a tech definition by its ID.
     *
     * @return array<string,mixed>|null
     */
    public function tech(int $id): ?array
    {
        return config("game.techs.{$id}");
    }

    /**
     * Get all tech definitions, keyed by tech ID.
     *
     * @return array<int, array<string,mixed>>
     */
    public function allTechs(): array
    {
        return config('game.techs', []);
    }

    /**
     * Get a building definition or throw if it does not exist.
     *
     * @throws InvalidArgumentException
     * @return array<string,mixed>
     */
    public function buildingOrFail(int $id): array
    {
        $data = $this->building($id);
        if ($data === null) {
            throw new InvalidArgumentException("Unknown building ID: {$id}");
        }
        return $data;
    }

    /**
     * Get a unit definition or throw if it does not exist.
     *
     * @throws InvalidArgumentException
     * @return array<string,mixed>
     */
    public function unitOrFail(int $id): array
    {
        $data = $this->unit($id);
        if ($data === null) {
            throw new InvalidArgumentException("Unknown unit ID: {$id}");
        }
        return $data;
    }

    /**
     * Get a tech definition or throw if it does not exist.
     *
     * @throws InvalidArgumentException
     * @return array<string,mixed>
     */
    public function techOrFail(int $id): array
    {
        $data = $this->tech($id);
        if ($data === null) {
            throw new InvalidArgumentException("Unknown tech ID: {$id}");
        }
        return $data;
    }
}
