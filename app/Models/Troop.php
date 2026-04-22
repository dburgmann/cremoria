<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Troop extends Model
{
    public $timestamps = false;

    protected $fillable = ['movement_id', 'unit_id', 'quantity'];

    public function movement(): BelongsTo
    {
        return $this->belongsTo(Movement::class);
    }
}
