<?php

namespace Tests\Feature;

use App\Models\Town;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Spatie\Permission\Models\Role::create(['name' => 'login', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::create(['name' => 'unverified', 'guard_name' => 'web']);
    }

    // --- Registration ---

    public function test_registration_page_loads(): void
    {
        $this->get(route('register'))
            ->assertStatus(200)
            ->assertSee('Registrieren');
    }

    public function test_registration_creates_user_and_town(): void
    {
        $this->post(route('register'), [
            'username'              => 'TestUser',
            'email'                 => 'test@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseHas('users', ['username' => 'TestUser']);
        $this->assertDatabaseHas('towns', []); // existence creator was called
    }

    public function test_registration_fails_with_duplicate_username(): void
    {
        User::factory()->create(['username' => 'Existing']);

        $this->post(route('register'), [
            'username'              => 'Existing',
            'email'                 => 'new@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('username');
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->post(route('register'), [
            'username'              => 'NewUser',
            'email'                 => 'taken@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('email');
    }

    public function test_registration_fails_with_password_mismatch(): void
    {
        $this->post(route('register'), [
            'username'              => 'TestUser',
            'email'                 => 'test@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'differentpassword',
        ])->assertSessionHasErrors('password');
    }

    public function test_registration_fails_with_non_alpha_username(): void
    {
        $this->post(route('register'), [
            'username'              => 'User 123!',
            'email'                 => 'test@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('username');
    }

    // --- Login ---

    public function test_login_page_loads(): void
    {
        $this->get(route('login'))
            ->assertStatus(200)
            ->assertSee('Anmelden');
    }

    public function test_login_succeeds_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);
        $user->assignRole('login');

        Town::create([
            'user_id' => $user->id,
            'name'    => 'Home',
            'x'       => 1,
            'y'       => 1,
        ]);

        $this->post(route('login'), [
            'username' => $user->username,
            'password' => 'password123',
        ])->assertRedirect(route('game.overview'));
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('correct')]);
        $user->assignRole('login');

        $this->post(route('login'), [
            'username' => $user->username,
            'password' => 'wrong',
        ])->assertSessionHasErrors('username');
    }

    public function test_login_fails_for_unverified_user(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $user->assignRole('unverified');

        $this->post(route('login'), [
            'username' => $user->username,
            'password' => 'password',
        ])->assertSessionHasErrors('username');
    }

    // --- Verification ---

    public function test_verify_valid_code_promotes_user(): void
    {
        $user = User::factory()->create(['reg_key' => 'validcode123']);
        $user->assignRole('unverified');

        $this->get(route('verify', ['code' => 'validcode123']))
            ->assertRedirect(route('login'));

        $user->refresh();
        $this->assertTrue($user->hasRole('login'));
        $this->assertNull($user->reg_key);
    }

    public function test_verify_invalid_code_redirects_with_error(): void
    {
        $this->get(route('verify', ['code' => 'invalidcode']))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('code');
    }

    // --- Logout ---

    public function test_logout_redirects_to_login(): void
    {
        $user = User::factory()->create();
        $user->assignRole('login');

        Town::create(['user_id' => $user->id, 'name' => 'Home', 'x' => 0, 'y' => 0]);

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    // --- Town ownership ---

    public function test_set_town_rejects_other_users_town(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user1->assignRole('login');

        $town1 = Town::create(['user_id' => $user1->id, 'name' => 'Town1', 'x' => 0, 'y' => 0]);
        $town2 = Town::create(['user_id' => $user2->id, 'name' => 'Town2', 'x' => 1, 'y' => 1]);

        $this->actingAs($user1)
            ->post(route('game.setTown'), ['town_id' => $town2->id])
            ->assertStatus(404); // findOrFail throws 404
    }
}
