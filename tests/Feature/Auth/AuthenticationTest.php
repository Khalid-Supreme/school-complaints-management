<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->role = Role::factory()->create(['slug' => 'complainant']);
        $this->user = User::factory()->create([
            'role_id' => $this->role->id,
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);
    }

    public function test_users_can_authenticate_with_valid_credentials(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'user', 'token']);
        
        $this->assertDatabaseHas('login_attempts', [
            'email' => 'test@example.com',
            'successful' => true,
        ]);
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422);
        
        $this->assertDatabaseHas('login_attempts', [
            'email' => 'test@example.com',
            'successful' => false,
        ]);
    }

    public function test_inactive_users_cannot_authenticate(): void
    {
        $inactiveUser = User::factory()->create([
            'role_id' => $this->role->id,
            'email' => 'inactive@example.com',
            'password' => Hash::make('password123'),
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'inactive@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseHas('login_attempts', [
            'email' => 'inactive@example.com',
            'successful' => false,
            'failure_reason' => 'Account inactive',
        ]);
    }
}
