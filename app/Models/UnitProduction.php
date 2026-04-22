<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitProduction extends Model
{
    public $timestamps = false;

    protected $fillable = ['unit_id', 'town_id', 'quantity', 'time'];

    public function town(): BelongsTo
    {
        return $this->belongsTo(Town::class);
    }
}
