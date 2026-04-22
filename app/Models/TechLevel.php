<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents the quantity/level of a given tech a user has researched.
 * Composite PK: (id = tech_type_id, user_id).
 */
class TechLevel extends Model
{
    public $timestamps = false;

    protected $table = 'techs';

    protected $fillable = ['id', 'user_id', 'quantity'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
