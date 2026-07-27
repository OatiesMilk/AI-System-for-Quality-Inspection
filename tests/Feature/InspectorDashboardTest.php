<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Batch;
use App\Models\Defect;
use App\Models\Inspection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InspectorDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function makeInspection(array $inspectionOverrides = []): Inspection
    {
        $batch = Batch::create([
            'batch_code' => 'TEST-'.fake()->unique()->numerify('###'),
            'production_date' => now(),
            'manufacturing_stage' => 'finishing',
        ]);

        return Inspection::create(array_merge([
            'batch_id' => $batch->id,
            'checkpoint' => 'finishing',
            'image_path' => null,
        ], $inspectionOverrides));
    }

    public function test_guests_cannot_view_the_inspector_dashboard(): void
    {
        $response = $this->get('/inspector');

        $response->assertRedirect('/login');
    }

    public function test_non_inspector_roles_are_forbidden_from_the_inspector_dashboard(): void
    {
        $manager = User::factory()->create(['role' => 'product_manager']);

        $response = $this->actingAs($manager)->get('/inspector');

        $response->assertForbidden();
    }

    public function test_inspector_dashboard_separates_pending_from_reviewed_inspections(): void
    {
        $inspector = User::factory()->create(['role' => 'quality_inspector']);

        $pending = $this->makeInspection();
        $reviewed = $this->makeInspection([
            'action' => 'pass',
            'inspector_id' => $inspector->id,
            'inspected_at' => now(),
        ]);

        $response = $this->actingAs($inspector)->get('/inspector');

        $response->assertOk();
        $response->assertSee($pending->batch->batch_code);
        $response->assertSee($reviewed->batch->batch_code);
        $response->assertSeeInOrder([
            'Pending Inspections',
            $pending->batch->batch_code,
            'Reviewed Inspections',
            $reviewed->batch->batch_code,
        ]);
    }

    public function test_reviewed_inspections_can_be_filtered_by_decision(): void
    {
        $inspector = User::factory()->create(['role' => 'quality_inspector']);

        $passed = $this->makeInspection(['action' => 'pass', 'inspector_id' => $inspector->id, 'inspected_at' => now()]);
        $reworked = $this->makeInspection(['action' => 'rework', 'inspector_id' => $inspector->id, 'inspected_at' => now()]);

        $response = $this->actingAs($inspector)->get('/inspector?decision=pass');

        $response->assertOk();
        $response->assertSee($passed->batch->batch_code);
        $response->assertDontSee($reworked->batch->batch_code);
    }

    public function test_reviewed_inspections_can_be_filtered_by_ai_override(): void
    {
        $inspector = User::factory()->create(['role' => 'quality_inspector']);

        $overridden = $this->makeInspection(['action' => 'pass', 'ai_override' => true, 'inspector_id' => $inspector->id, 'inspected_at' => now()]);
        $notOverridden = $this->makeInspection(['action' => 'pass', 'ai_override' => false, 'inspector_id' => $inspector->id, 'inspected_at' => now()]);

        $response = $this->actingAs($inspector)->get('/inspector?ai_override=1');

        $response->assertOk();
        $response->assertSee($overridden->batch->batch_code);
        $response->assertDontSee($notOverridden->batch->batch_code);
    }

    public function test_reviewed_inspections_filter_rejects_an_invalid_date(): void
    {
        $inspector = User::factory()->create(['role' => 'quality_inspector']);

        $response = $this->actingAs($inspector)->get('/inspector?date_from=not-a-date');

        $response->assertSessionHasErrors('date_from');
    }

    public function test_inspector_can_view_an_inspection_with_its_defects(): void
    {
        $inspector = User::factory()->create(['role' => 'quality_inspector']);
        $inspection = $this->makeInspection();

        Defect::create([
            'inspection_id' => $inspection->id,
            'defect_type' => 'scratch',
            'confidence_score' => 0.91,
            'bounding_box' => ['x' => 0.2, 'y' => 0.3, 'width' => 0.1, 'height' => 0.1],
        ]);

        $response = $this->actingAs($inspector)
            ->get("/inspector/inspections/{$inspection->id}");

        $response->assertOk();
        $response->assertSee('scratch');
        $response->assertSee('91.0%');
    }

    public function test_inspector_can_confirm_defects_and_pass_an_inspection(): void
    {
        $inspector = User::factory()->create(['role' => 'quality_inspector']);
        $inspection = $this->makeInspection();

        $defect = Defect::create([
            'inspection_id' => $inspection->id,
            'defect_type' => 'hole',
            'confidence_score' => 0.82,
            'bounding_box' => null,
        ]);

        $response = $this->actingAs($inspector)
            ->patch("/inspector/inspections/{$inspection->id}", [
                'action' => 'pass',
                'defects' => [$defect->id => '1'],
            ]);

        $response->assertRedirect(route('dashboard.inspector'));

        $inspection->refresh();
        $defect->refresh();

        $this->assertSame('pass', $inspection->action);
        $this->assertFalse($inspection->ai_override);
        $this->assertSame($inspector->id, $inspection->inspector_id);
        $this->assertTrue($defect->confirmed);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'inspection.validated',
            'user_id' => $inspector->id,
        ]);
    }

    public function test_dismissing_a_defect_marks_the_inspection_as_an_ai_override(): void
    {
        $inspector = User::factory()->create(['role' => 'quality_inspector']);
        $inspection = $this->makeInspection();

        $defect = Defect::create([
            'inspection_id' => $inspection->id,
            'defect_type' => 'glue',
            'confidence_score' => 0.70,
            'bounding_box' => null,
        ]);

        $this->actingAs($inspector)
            ->patch("/inspector/inspections/{$inspection->id}", [
                'action' => 'pass',
                'defects' => [], // unchecked = dismissed as a false positive
            ]);

        $inspection->refresh();
        $defect->refresh();

        $this->assertTrue($inspection->ai_override);
        $this->assertFalse($defect->confirmed);
    }

    public function test_an_invalid_action_is_rejected(): void
    {
        $inspector = User::factory()->create(['role' => 'quality_inspector']);
        $inspection = $this->makeInspection();

        $response = $this->actingAs($inspector)
            ->patch("/inspector/inspections/{$inspection->id}", [
                'action' => 'not-a-real-action',
            ]);

        $response->assertSessionHasErrors('action');
        $this->assertNull($inspection->fresh()->action);
    }
}
