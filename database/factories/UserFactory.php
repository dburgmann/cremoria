<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username'        => fake()->unique()->userName(),
            'email'           => fake()->unique()->safeEmail(),
            'password'        => static::$password ??= Hash::make('password'),
            'reg_key'         => null,
            'current_research'=> null,
            'research_time'   => null,
            'research_pts'    => 0,
            'current_rsc_pts' => 0,
            'building_pts'    => 0,
            'total_pts'       => 0,
            'remember_token'  => Str::random(10),
        ];
    }
}
