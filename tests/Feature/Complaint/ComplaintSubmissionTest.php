<?php

namespace Tests\Feature\Complaint;

use App\Models\Complaint;
use App\Models\ComplaintCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->roleComplainant = Role::factory()->create(['slug' => 'complainant']);
        $this->roleAdmin = Role::factory()->create(['slug' => 'admin']);
        
        $this->complainant = User::factory()->create(['role_id' => $this->roleComplainant->id]);
        $this->admin = User::factory()->create(['role_id' => $this->roleAdmin->id]);
        
        $this->category = ComplaintCategory::factory()->create([
            'default_priority' => 'high'
        ]);
    }

    public function test_complainant_can_submit_complaint(): void
    {
        $response = $this->actingAs($this->complainant)->postJson('/api/complaints', [
            'category_id' => $this->category->id,
            'title' => 'My test complaint title',
            'description' => 'This is a detailed description of the complaint.',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'complaint' => ['reference_no', 'status']
        ]);

        $complaint = Complaint::first();
        
        $this->assertEquals($this->complainant->id, $complaint->complainant_id);
        $this->assertEquals($this->category->id, $complaint->category_id);
        $this->assertEquals('submitted', $complaint->status);
        $this->assertEquals('high', $complaint->priority);
        
        // Assert that data is encrypted in the database
        $this->assertNotEquals('My test complaint title', $complaint->title_encrypted);
        $this->assertNotEquals('This is a detailed description of the complaint.', $complaint->description_encrypted);
    }

    public function test_admin_cannot_submit_complaint(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/complaints', [
            'category_id' => $this->category->id,
            'title' => 'Admin test complaint',
            'description' => 'Admin should not be able to submit this.',
        ]);

        $response->assertStatus(403);
    }

    public function test_complainant_can_view_their_own_complaints(): void
    {
        Complaint::factory()->create([
            'complainant_id' => $this->complainant->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->complainant)->getJson('/api/complaints');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_complainant_cannot_view_others_complaints(): void
    {
        $otherComplainant = User::factory()->create(['role_id' => $this->roleComplainant->id]);
        
        $complaint = Complaint::factory()->create([
            'complainant_id' => $otherComplainant->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->complainant)->getJson("/api/complaints/{$complaint->id}");

        $response->assertStatus(403);
    }
}
