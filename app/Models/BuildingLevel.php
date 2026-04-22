<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents the quantity of a given building type in a town.
 * Composite PK: (id = building_type_id, town_id).
 */
class BuildingLevel extends Model
{
    public $timestamps = false;

    protected $table = 'buildings';

    protected $fillable = ['id', 'town_id', 'quantity'];

    public function town(): BelongsTo
    {
        return $this->belongsTo(Town::class);
    }
}
