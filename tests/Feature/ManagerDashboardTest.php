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
            'manufacturing_stage' => 'pre_assembly',
        ]);

        $inspection = Inspection::create([
            'batch_id' => $batch->id,
            'checkpoint' => 'pre_assembly',
        ]);

        Defect::create([
            'inspection_id' => $inspection->id,
            'defect_type' => 'scratch',
            'confidence_score' => 0.88,
        ]);

        $overview = $this->actingAs($manager)->get('/manager');
        $overview->assertOk();
        $overview->assertSee('MGR-001');

        $quality = $this->actingAs($manager)->get('/manager?tab=quality');
        $quality->assertOk();
        $quality->assertSee('scratch');
    }

    public function test_manager_dashboard_shows_ai_override_analytics(): void
    {
        $manager = User::factory()->create(['role' => 'product_manager']);
        $inspector = User::factory()->create(['role' => 'quality_inspector']);

        $batch = Batch::create([
            'batch_code' => 'MGR-OVERRIDE',
            'production_date' => now(),
            'manufacturing_stage' => 'pre_assembly',
        ]);

        $overridden = Inspection::create([
            'batch_id' => $batch->id,
            'checkpoint' => 'pre_assembly',
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
            'checkpoint' => 'pre_assembly',
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

    public function test_manager_dashboard_shows_leather_yield_summary(): void
    {
        $manager = User::factory()->create(['role' => 'product_manager']);

        $batch = Batch::create([
            'batch_code' => 'YIELD-001',
            'production_date' => now(),
            'expected_pieces' => 100,
            'manufacturing_stage' => 'pre_assembly',
        ]);

        Inspection::create(['batch_id' => $batch->id, 'checkpoint' => 'pre_assembly', 'action' => 'pass', 'inspected_at' => now()]);
        Inspection::create(['batch_id' => $batch->id, 'checkpoint' => 'pre_assembly', 'action' => 'pass', 'inspected_at' => now()]);
        Inspection::create(['batch_id' => $batch->id, 'checkpoint' => 'pre_assembly', 'action' => 'reject', 'inspected_at' => now()]);

        $response = $this->actingAs($manager)->get('/manager');

        $response->assertOk();
        $response->assertViewHas('totalExpectedPieces', 100);
        $response->assertViewHas('totalProducedPieces', 3);
        $response->assertViewHas('totalPassedPieces', 2);
        $response->assertViewHas('overallYieldRate', 2.0);
    }

    public function test_manager_dashboard_shows_rework_turnaround_by_station(): void
    {
        $manager = User::factory()->create(['role' => 'product_manager']);

        $batch = Batch::create([
            'batch_code' => 'TURN-001',
            'production_date' => now(),
            'manufacturing_stage' => 'pre_assembly',
        ]);

        Inspection::create([
            'batch_id' => $batch->id,
            'checkpoint' => 'pre_assembly',
            'action' => 'rework',
            'rework_station' => 'cutting',
            'rework_status' => 'completed',
            'inspected_at' => now()->subHours(3),
            'reworked_at' => now()->subHours(1),
        ]);

        $response = $this->actingAs($manager)->get('/manager');

        $response->assertOk();
        $response->assertViewHas('reworkStationStats', fn ($stats) =>
            $stats->get('cutting')['total'] === 1
            && $stats->get('cutting')['resolved'] === 1
            && $stats->get('cutting')['avg_turnaround'] === '2h 0m'
        );
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
            'manufacturing_stage' => 'pre_assembly',
        ]);

        $response->assertRedirect(route('dashboard.manager'));

        $this->assertDatabaseHas('batches', [
            'batch_code' => 'BATCH-1-20260701',
            'expected_pieces' => 40,
            'manufacturing_stage' => 'pre_assembly',
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
            'manufacturing_stage' => 'pre_assembly',
        ]);

        $response = $this->actingAs($manager)->post('/manager/batches', [
            'batch_code' => 'DUPLICATE-001',
            'production_date' => now()->toDateString(),
            'expected_pieces' => 40,
            'manufacturing_stage' => 'pre_assembly',
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
            'manufacturing_stage' => 'pre_assembly',
        ]);

        $response->assertSessionHasErrors('expected_pieces');
    }

    public function test_manager_can_manually_close_a_batch(): void
    {
        $manager = User::factory()->create(['role' => 'product_manager']);

        $batch = Batch::create([
            'batch_code' => 'CLOSE-001',
            'production_date' => now(),
            'expected_pieces' => 40,
            'manufacturing_stage' => 'pre_assembly',
        ]);

        Inspection::create(['batch_id' => $batch->id, 'checkpoint' => 'pre_assembly', 'action' => 'pass', 'inspected_at' => now()]);

        $response = $this->actingAs($manager)->patch("/manager/batches/{$batch->id}/close");

        $response->assertRedirect(route('dashboard.manager'));

        $this->assertSame('completed', $batch->fresh()->status);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'batch.closed',
            'user_id' => $manager->id,
        ]);
    }

    public function test_manager_dashboard_shows_batch_status_and_flags_under_produced_batches(): void
    {
        $manager = User::factory()->create(['role' => 'product_manager']);

        Batch::create([
            'batch_code' => 'STATUS-OPEN',
            'production_date' => now(),
            'expected_pieces' => 40,
            'manufacturing_stage' => 'pre_assembly',
        ]);

        $underBatch = Batch::create([
            'batch_code' => 'STATUS-UNDER',
            'production_date' => now(),
            'expected_pieces' => 40,
            'status' => 'completed',
            'manufacturing_stage' => 'pre_assembly',
        ]);
        Inspection::create(['batch_id' => $underBatch->id, 'checkpoint' => 'pre_assembly', 'action' => 'pass', 'inspected_at' => now()]);

        $response = $this->actingAs($manager)->get('/manager');

        $response->assertOk();
        $response->assertSee('Open');
        $response->assertSee('Under (1/40)');
    }
}
