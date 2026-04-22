<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'username',
        'email',
        'password',
        'reg_key',
        'current_research',
        'research_time',
        'research_pts',
        'current_rsc_pts',
        'building_pts',
        'total_pts',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function towns(): HasMany
    {
        return $this->hasMany(Town::class);
    }

    public function techs(): HasMany
    {
        return $this->hasMany(TechLevel::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(Movement::class, 'owner');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'receiver');
    }

    /**
     * Get the quantity of a specific tech the user has researched.
     */
    public function techQuantity(int $techId): int
    {
        return $this->techs->firstWhere('id', $techId)?->quantity ?? 0;
    }
}
