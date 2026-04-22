<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Movement extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'owner',
        'start',
        'start_x',
        'start_y',
        'dest_x',
        'dest_y',
        'gold',
        'wood',
        'stone',
        'iron',
        'action',
        'duration',
        'arrival',
    ];

    public function ownerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner');
    }

    public function troops(): HasMany
    {
        return $this->hasMany(Troop::class);
    }
}
