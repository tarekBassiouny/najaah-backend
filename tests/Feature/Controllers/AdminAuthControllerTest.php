<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_succeeds_with_valid_credentials(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'secret123',
        ]);

        $response = $this->postJson('/api/v1/admin/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'secret123',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['user', 'token']);
        $this->assertNotNull($user->fresh());
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/v1/admin/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong',
        ]);

        $response->assertStatus(401);
    }

    public function test_me_returns_authenticated_admin(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/admin/auth/me');

        $response->assertOk()->assertJsonStructure(['user']);
    }

    public function test_logout_revokes_token(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/admin/auth/logout');

        $response->assertOk()->assertJson(['message' => 'Logged out']);
    }
}
