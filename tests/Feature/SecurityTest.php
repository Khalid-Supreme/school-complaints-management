<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup basic roles
        Role::create(['name' => 'Administrator', 'slug' => 'admin', 'description' => 'System Admin', 'is_system' => true]);
        Role::create(['name' => 'Staff', 'slug' => 'staff', 'description' => 'School Staff', 'is_system' => true]);
        Role::create(['name' => 'Complainant', 'slug' => 'complainant', 'description' => 'Student/Parent', 'is_system' => true]);
    }

    /** @test */
    public function test_ips_middleware_blocks_after_threshold()
    {
        $user = User::factory()->create(['role_id' => Role::where('slug', 'admin')->first()->id]);
        $this->actingAs($user);

        $endpoint = '/api/user';
        
        // Simulate 100 requests to hit the threshold
        for ($i = 0; $i < 100; $i++) {
            $this->get($endpoint);
        }

        // The 101st request should be blocked
        $response = $this->get($endpoint);
        
        $response->assertStatus(429);
        $response->assertJsonFragment(['code' => 'IP_BLOCKED']);
    }

    /** @test */
    public function test_security_dashboard_requires_admin()
    {
        $staff = User::factory()->create(['role_id' => Role::where('slug', 'staff')->first()->id]);
        
        $this->actingAs($staff);
        $response = $this->get('/api/security/dashboard');
        
        $response->assertStatus(403);
    }

    /** @test */
    public function test_admin_can_access_security_dashboard()
    {
        $admin = User::factory()->create(['role_id' => Role::where('slug', 'admin')->first()->id]);
        
        $this->actingAs($admin);
        $response = $this->get('/api/security/dashboard');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'summary',
            'auth_health',
            'ips_status',
            'alerts'
        ]);
    }
}
