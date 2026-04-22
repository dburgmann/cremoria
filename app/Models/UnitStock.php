<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents the quantity of a given unit type in a town.
 * Composite PK: (unit_id, town_id).
 */
class UnitStock extends Model
{
    public $timestamps = false;

    protected $table = 'units';

    protected $primaryKey = 'unit_id';

    protected $fillable = ['unit_id', 'town_id', 'quantity'];

    public function town(): BelongsTo
    {
        return $this->belongsTo(Town::class);
    }
}
