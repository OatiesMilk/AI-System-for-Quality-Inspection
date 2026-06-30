<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InspectionIngestApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeBatch(): Batch
    {
        return Batch::create([
            'batch_code' => 'API-'.fake()->unique()->numerify('###'),
            'production_date' => now(),
            'manufacturing_stage' => 'finishing',
        ]);
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $batch = $this->makeBatch();

        $response = $this->postJson('/api/inspections', [
            'batch_id' => $batch->id,
            'checkpoint' => 'finishing',
        ]);

        $response->assertUnauthorized();
    }

    public function test_a_token_without_the_required_ability_is_rejected(): void
    {
        $service = User::factory()->create(['role' => 'system_admin']);
        Sanctum::actingAs($service, ['some:other:ability']);

        $batch = $this->makeBatch();

        $response = $this->postJson('/api/inspections', [
            'batch_id' => $batch->id,
            'checkpoint' => 'finishing',
        ]);

        $response->assertForbidden();
    }

    public function test_it_creates_an_inspection_with_defects_and_stores_the_image(): void
    {
        Storage::fake('public');

        $service = User::factory()->create(['role' => 'system_admin']);
        Sanctum::actingAs($service, ['inspections:create']);

        $batch = $this->makeBatch();
        $image = UploadedFile::fake()->create('inspection.jpg', 50, 'image/jpeg');

        $response = $this->postJson('/api/inspections', [
            'batch_id' => $batch->id,
            'checkpoint' => 'finishing',
            'image' => $image,
            'defects' => [
                [
                    'defect_type' => 'scratch',
                    'confidence_score' => 0.87,
                    'bounding_box' => ['x' => 0.2, 'y' => 0.3, 'width' => 0.1, 'height' => 0.1],
                ],
                [
                    'defect_type' => 'excess_glue',
                    'confidence_score' => 0.6,
                ],
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonStructure(['message', 'inspection_id', 'defect_count']);
        $response->assertJson(['defect_count' => 2]);

        $this->assertDatabaseHas('inspections', [
            'batch_id' => $batch->id,
            'checkpoint' => 'finishing',
            'action' => null,
        ]);

        $this->assertDatabaseHas('defects', [
            'defect_type' => 'scratch',
            'confidence_score' => 0.87,
        ]);

        $inspectionId = $response->json('inspection_id');
        $inspection = \App\Models\Inspection::find($inspectionId);

        Storage::disk('public')->assertExists($inspection->image_path);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'inspection.ingested',
            'user_id' => $service->id,
        ]);
    }

    public function test_it_rejects_a_nonexistent_batch_id(): void
    {
        $service = User::factory()->create(['role' => 'system_admin']);
        Sanctum::actingAs($service, ['inspections:create']);

        $response = $this->postJson('/api/inspections', [
            'batch_id' => 999999,
            'checkpoint' => 'finishing',
            'image' => UploadedFile::fake()->create('inspection.jpg', 50, 'image/jpeg'),
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('batch_id');
    }

    public function test_it_rejects_an_invalid_defect_type(): void
    {
        $service = User::factory()->create(['role' => 'system_admin']);
        Sanctum::actingAs($service, ['inspections:create']);

        $batch = $this->makeBatch();

        $response = $this->postJson('/api/inspections', [
            'batch_id' => $batch->id,
            'checkpoint' => 'finishing',
            'image' => UploadedFile::fake()->create('inspection.jpg', 50, 'image/jpeg'),
            'defects' => [
                ['defect_type' => 'not-a-real-defect', 'confidence_score' => 0.5],
            ],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('defects.0.defect_type');
    }

    public function test_it_rejects_a_confidence_score_outside_zero_to_one(): void
    {
        $service = User::factory()->create(['role' => 'system_admin']);
        Sanctum::actingAs($service, ['inspections:create']);

        $batch = $this->makeBatch();

        $response = $this->postJson('/api/inspections', [
            'batch_id' => $batch->id,
            'checkpoint' => 'finishing',
            'image' => UploadedFile::fake()->create('inspection.jpg', 50, 'image/jpeg'),
            'defects' => [
                ['defect_type' => 'scratch', 'confidence_score' => 1.5],
            ],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('defects.0.confidence_score');
    }

    public function test_it_creates_an_inspection_with_no_defects(): void
    {
        $service = User::factory()->create(['role' => 'system_admin']);
        Sanctum::actingAs($service, ['inspections:create']);

        $batch = $this->makeBatch();

        $response = $this->postJson('/api/inspections', [
            'batch_id' => $batch->id,
            'checkpoint' => 'preparation',
            'image' => UploadedFile::fake()->create('clean.jpg', 50, 'image/jpeg'),
        ]);

        $response->assertCreated();
        $response->assertJson(['defect_count' => 0]);
    }
}
