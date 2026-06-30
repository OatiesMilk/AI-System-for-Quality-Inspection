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
            'defect_type' => 'excess_glue',
            'confidence_score' => 0.74,
            'bounding_box' => ['x' => 0.4, 'y' => 0.5, 'width' => 0.15, 'height' => 0.1],
        ]);

        $response = $this->actingAs($constructor)
            ->get("/constructor/inspections/{$inspection->id}");

        $response->assertOk();
        $response->assertSee('excess glue');
        $response->assertSee('74.0%');
    }

    public function test_constructor_with_a_shift_only_sees_reworks_from_their_own_shift(): void
    {
        $amConstructor = User::factory()->create(['role' => 'shoe_constructor', 'shift' => 'am']);

        $amRework = $this->makeReworkInspection(batchOverrides: ['shift' => 'am']);
        $pmRework = $this->makeReworkInspection(batchOverrides: ['shift' => 'pm']);

        $response = $this->actingAs($amConstructor)->get('/constructor');

        $response->assertOk();
        $response->assertSee($amRework->batch->batch_code);
        $response->assertDontSee($pmRework->batch->batch_code);
    }

    public function test_constructor_without_a_shift_sees_all_reworks(): void
    {
        $unassignedConstructor = User::factory()->create(['role' => 'shoe_constructor', 'shift' => null]);

        $amRework = $this->makeReworkInspection(batchOverrides: ['shift' => 'am']);
        $pmRework = $this->makeReworkInspection(batchOverrides: ['shift' => 'pm']);

        $response = $this->actingAs($unassignedConstructor)->get('/constructor');

        $response->assertOk();
        $response->assertSee($amRework->batch->batch_code);
        $response->assertSee($pmRework->batch->batch_code);
    }

    public function test_constructor_can_mark_a_rework_as_resolved(): void
    {
        $constructor = User::factory()->create(['role' => 'shoe_constructor']);
        $inspection = $this->makeReworkInspection();

        $response = $this->actingAs($constructor)
            ->patch("/constructor/inspections/{$inspection->id}/resolve");

        $response->assertRedirect(route('dashboard.constructor'));

        $inspection->refresh();
        $this->assertNotNull($inspection->reworked_at);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'inspection.reworked',
            'user_id' => $constructor->id,
        ]);
    }
}
