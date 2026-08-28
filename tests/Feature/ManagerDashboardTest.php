<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Defect;
use App\Models\Inspection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_manager_roles_are_forbidden_from_the_manager_dashboard(): void
    {
        $inspector = User::factory()->create(['role' => 'quality_inspector']);

        $response = $this->actingAs($inspector)->get('/manager');

        $response->assertForbidden();
    }

    public function test_manager_dashboard_shows_defect_counts_and_recent_batches(): void
    {
        $manager = User::factory()->create(['role' => 'product_manager']);

        $batch = Batch::create([
            'batch_code' => 'MGR-001',
            'production_date' => now(),
            'manufacturing_stage' => 'finishing',
        ]);

        $inspection = Inspection::create([
            'batch_id' => $batch->id,
            'checkpoint' => 'finishing',
        ]);

        Defect::create([
            'inspection_id' => $inspection->id,
            'defect_type' => 'scratch',
            'confidence_score' => 0.88,
        ]);

        $response = $this->actingAs($manager)->get('/manager');

        $response->assertOk();
        $response->assertSee('MGR-001');
        $response->assertSee('scratch');
    }

    public function test_manager_dashboard_shows_ai_override_analytics(): void
    {
        $manager = User::factory()->create(['role' => 'product_manager']);
        $inspector = User::factory()->create(['role' => 'quality_inspector']);

        $batch = Batch::create([
            'batch_code' => 'MGR-OVERRIDE',
            'production_date' => now(),
            'manufacturing_stage' => 'finishing',
        ]);

        $overridden = Inspection::create([
            'batch_id' => $batch->id,
            'checkpoint' => 'finishing',
            'action' => 'pass',
            'ai_override' => true,
            'inspector_id' => $inspector->id,
            'inspected_at' => now(),
        ]);

        Defect::create([
            'inspection_id' => $overridden->id,
            'defect_type' => 'hole',
            'confidence_score' => 0.6,
            'confirmed' => false,
        ]);

        $clean = Inspection::create([
            'batch_id' => $batch->id,
            'checkpoint' => 'finishing',
            'action' => 'pass',
            'ai_override' => false,
            'inspector_id' => $inspector->id,
            'inspected_at' => now(),
        ]);

        Defect::create([
            'inspection_id' => $clean->id,
            'defect_type' => 'scratch',
            'confidence_score' => 0.9,
            'confirmed' => true,
        ]);

        $response = $this->actingAs($manager)->get('/manager');

        $response->assertOk();
        $response->assertViewHas('reviewedCount', 2);
        $response->assertViewHas('overriddenCount', 1);
        $response->assertViewHas('overrideRate', 50.0);
        $response->assertViewHas('aiOverrideCounts', fn ($counts) => $counts->get('hole') === 1 && ! $counts->has('scratch'));
    }

    public function test_manager_cannot_access_user_account_creation(): void
    {
        $manager = User::factory()->create(['role' => 'product_manager']);

        $response = $this->actingAs($manager)->get('/manager/users/create');

        $response->assertNotFound();
    }

    public function test_non_manager_roles_are_forbidden_from_creating_batches(): void
    {
        $constructor = User::factory()->create(['role' => 'shoe_constructor']);

        $response = $this->actingAs($constructor)->get('/manager/batches/create');

        $response->assertForbidden();
    }

    public function test_manager_can_create_a_batch(): void
    {
        $manager = User::factory()->create(['role' => 'product_manager']);

        $response = $this->actingAs($manager)->post('/manager/batches', [
            'batch_code' => 'BATCH-1-20260701',
            'production_date' => '2026-07-01',
            'expected_pieces' => 40,
            'manufacturing_stage' => 'finishing',
        ]);

        $response->assertRedirect(route('dashboard.manager'));

        $this->assertDatabaseHas('batches', [
            'batch_code' => 'BATCH-1-20260701',
            'expected_pieces' => 40,
            'manufacturing_stage' => 'finishing',
            'created_by' => $manager->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'batch.created',
            'user_id' => $manager->id,
        ]);
    }

    public function test_batch_code_must_be_unique(): void
    {
        $manager = User::factory()->create(['role' => 'product_manager']);

        Batch::create([
            'batch_code' => 'DUPLICATE-001',
            'production_date' => now(),
            'expected_pieces' => 40,
            'manufacturing_stage' => 'finishing',
        ]);

        $response = $this->actingAs($manager)->post('/manager/batches', [
            'batch_code' => 'DUPLICATE-001',
            'production_date' => now()->toDateString(),
            'expected_pieces' => 40,
            'manufacturing_stage' => 'finishing',
        ]);

        $response->assertSessionHasErrors('batch_code');
    }

    public function test_batch_requires_expected_pieces(): void
    {
        $manager = User::factory()->create(['role' => 'product_manager']);

        $response = $this->actingAs($manager)->post('/manager/batches', [
            'batch_code' => 'BATCH-INVALID',
            'production_date' => now()->toDateString(),
            'expected_pieces' => 0,
            'manufacturing_stage' => 'finishing',
        ]);

        $response->assertSessionHasErrors('expected_pieces');
    }
}
