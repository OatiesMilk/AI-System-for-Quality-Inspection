<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Defect;
use App\Models\Inspection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConstructorDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function makeReworkInspection(array $overrides = [], array $batchOverrides = []): Inspection
    {
        $batch = Batch::create(array_merge([
            'batch_code' => 'CTOR-'.fake()->unique()->numerify('###'),
            'production_date' => now(),
            'manufacturing_stage' => 'finishing',
        ], $batchOverrides));

        return Inspection::create(array_merge([
            'batch_id' => $batch->id,
            'checkpoint' => 'finishing',
            'action' => 'rework',
            'rework_status' => 'not_started',
            'inspected_at' => now(),
        ], $overrides));
    }

    public function test_non_constructor_roles_are_forbidden_from_the_constructor_dashboard(): void
    {
        $inspector = User::factory()->create(['role' => 'quality_inspector']);

        $response = $this->actingAs($inspector)->get('/constructor');

        $response->assertForbidden();
    }

    public function test_constructor_dashboard_separates_active_from_resolved_reworks(): void
    {
        $constructor = User::factory()->create(['role' => 'shoe_constructor']);

        $active = $this->makeReworkInspection();
        $resolved = $this->makeReworkInspection(['reworked_at' => now()]);

        $response = $this->actingAs($constructor)->get('/constructor');

        $response->assertOk();
        $response->assertSeeInOrder([
            'Rework Notifications',
            $active->batch->batch_code,
            'Past Reworks',
            $resolved->batch->batch_code,
        ]);
    }

    public function test_constructor_can_view_an_inspections_defect_image_detail(): void
    {
        $constructor = User::factory()->create(['role' => 'shoe_constructor']);
        $inspection = $this->makeReworkInspection();

        Defect::create([
            'inspection_id' => $inspection->id,
            'defect_type' => 'glue',
            'confidence_score' => 0.74,
            'bounding_box' => ['x' => 0.4, 'y' => 0.5, 'width' => 0.15, 'height' => 0.1],
        ]);

        $response = $this->actingAs($constructor)
            ->get("/constructor/inspections/{$inspection->id}");

        $response->assertOk();
        $response->assertSee('excess glue');
        $response->assertSee('74.0%');
    }

    public function test_constructor_dashboard_shows_the_responsible_station(): void
    {
        $constructor = User::factory()->create(['role' => 'shoe_constructor']);
        $inspection = $this->makeReworkInspection(['rework_station' => 'upper_making']);

        $response = $this->actingAs($constructor)->get('/constructor');

        $response->assertOk();
        $response->assertSee('upper making');
    }

    public function test_constructor_sees_all_reworks(): void
    {
        $constructor = User::factory()->create(['role' => 'shoe_constructor']);

        $firstRework = $this->makeReworkInspection();
        $secondRework = $this->makeReworkInspection();

        $response = $this->actingAs($constructor)->get('/constructor');

        $response->assertOk();
        $response->assertSee($firstRework->batch->batch_code);
        $response->assertSee($secondRework->batch->batch_code);
    }

    public function test_constructor_can_start_progress_on_a_rework(): void
    {
        $constructor = User::factory()->create(['role' => 'shoe_constructor']);
        $inspection = $this->makeReworkInspection();

        $response = $this->actingAs($constructor)
            ->patch("/constructor/inspections/{$inspection->id}/status", [
                'rework_status' => 'in_progress',
            ]);

        $response->assertRedirect(route('dashboard.constructor'));

        $inspection->refresh();
        $this->assertSame('in_progress', $inspection->rework_status);
        $this->assertNull($inspection->reworked_at);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'inspection.rework_status_updated',
            'user_id' => $constructor->id,
        ]);
    }

    public function test_constructor_can_mark_a_rework_as_completed(): void
    {
        $constructor = User::factory()->create(['role' => 'shoe_constructor']);
        $inspection = $this->makeReworkInspection(['rework_status' => 'in_progress']);

        $response = $this->actingAs($constructor)
            ->patch("/constructor/inspections/{$inspection->id}/status", [
                'rework_status' => 'completed',
            ]);

        $response->assertRedirect(route('dashboard.constructor'));

        $inspection->refresh();
        $this->assertSame('completed', $inspection->rework_status);
        $this->assertNotNull($inspection->reworked_at);
        $this->assertSame($constructor->id, $inspection->resolved_by);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'inspection.reworked',
            'user_id' => $constructor->id,
        ]);
    }

    public function test_constructor_can_revert_a_rework_mistakenly_marked_completed(): void
    {
        $constructor = User::factory()->create(['role' => 'shoe_constructor']);
        $inspection = $this->makeReworkInspection([
            'rework_status' => 'completed',
            'reworked_at' => now(),
            'resolved_by' => $constructor->id,
        ]);

        $response = $this->actingAs($constructor)
            ->patch("/constructor/inspections/{$inspection->id}/status", [
                'rework_status' => 'in_progress',
            ]);

        $response->assertRedirect(route('dashboard.constructor'));

        $inspection->refresh();
        $this->assertSame('in_progress', $inspection->rework_status);
        $this->assertNull($inspection->reworked_at);
        $this->assertNull($inspection->resolved_by);

        // Reverted items move back out of "Past Reworks" into the active list.
        $dashboard = $this->actingAs($constructor)->get('/constructor');
        $dashboard->assertSeeInOrder(['Rework Notifications', $inspection->batch->batch_code]);
    }
}
