<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Town extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'x',
        'y',
        'gold',
        'wood',
        'stone',
        'iron',
        'gold_inc',
        'wood_inc',
        'stone_inc',
        'iron_inc',
        'iron_inc_max',
        'building_time',
        'current_building',
        'current_bld_pts',
        'building_pts',
        'unit_cap_used',
        'unit_cap',
        'mov_cap',
        'mov_cap_used',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function buildings(): HasMany
    {
        return $this->hasMany(BuildingLevel::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(UnitStock::class);
    }

    public function unitProductions(): HasMany
    {
        return $this->hasMany(UnitProduction::class);
    }

    /**
     * Get the quantity of a specific building type this town has.
     */
    public function buildingQuantity(int $buildingId): int
    {
        return $this->buildings->firstWhere('id', $buildingId)?->quantity ?? 0;
    }

    /**
     * Get the quantity of a specific unit type in this town.
     */
    public function unitQuantity(int $unitId): int
    {
        return $this->units->firstWhere('unit_id', $unitId)?->quantity ?? 0;
    }
}
