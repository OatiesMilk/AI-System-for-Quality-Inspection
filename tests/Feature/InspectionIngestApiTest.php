<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
            'manufacturing_stage' => 'pre_assembly',
        ]);
    }

    /**
     * UploadedFile::fake()->create() produces a zero-byte file on some
     * environments, which is useless for asserting the bytes actually
     * round-trip through storage, and the "image" validation rule requires
     * genuine image content. This writes a real 1x1 PNG instead.
     */
    private function fakeImageWithContent(string $name = 'inspection.png'): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');

        $path = tempnam(sys_get_temp_dir(), 'cpoint_test_image');
        file_put_contents($path, $png);

        return new UploadedFile($path, $name, 'image/png', null, true);
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $batch = $this->makeBatch();

        $response = $this->postJson('/api/inspections', [
            'batch_id' => $batch->id,
            'checkpoint' => 'pre_assembly',
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
            'checkpoint' => 'pre_assembly',
        ]);

        $response->assertForbidden();
    }

    public function test_it_creates_an_inspection_with_defects_and_stores_the_image(): void
    {
        $service = User::factory()->create(['role' => 'system_admin']);
        Sanctum::actingAs($service, ['inspections:create']);

        $batch = $this->makeBatch();
        $image = $this->fakeImageWithContent();

        $response = $this->postJson('/api/inspections', [
            'batch_id' => $batch->id,
            'checkpoint' => 'pre_assembly',
            'component_type' => 'component_a',
            'image' => $image,
            'defects' => [
                [
                    'defect_type' => 'scratch',
                    'confidence_score' => 0.87,
                    'bounding_box' => ['x' => 0.2, 'y' => 0.3, 'width' => 0.1, 'height' => 0.1],
                ],
                [
                    'defect_type' => 'glue',
                    'confidence_score' => 0.6,
                ],
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonStructure(['message', 'inspection_id', 'defect_count']);
        $response->assertJson(['defect_count' => 2]);

        $this->assertDatabaseHas('inspections', [
            'batch_id' => $batch->id,
            'checkpoint' => 'pre_assembly',
            'action' => null,
        ]);

        $this->assertDatabaseHas('defects', [
            'defect_type' => 'scratch',
            'confidence_score' => 0.87,
        ]);

        $inspectionId = $response->json('inspection_id');
        $inspection = \App\Models\Inspection::find($inspectionId);

        $this->assertTrue($inspection->hasStoredImage());
        $this->assertSame('image/png', $inspection->image_mime);
        $this->assertSame(file_get_contents($image->getRealPath()), base64_decode($inspection->image_data));

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
            'checkpoint' => 'pre_assembly',
            'image' => UploadedFile::fake()->create('inspection.jpg', 50, 'image/jpeg'),
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('batch_id');
    }

    public function test_it_rejects_a_piece_submitted_to_an_already_closed_batch(): void
    {
        $service = User::factory()->create(['role' => 'system_admin']);
        Sanctum::actingAs($service, ['inspections:create']);

        $batch = $this->makeBatch();
        $batch->update(['status' => 'completed']);

        $response = $this->postJson('/api/inspections', [
            'batch_id' => $batch->id,
            'checkpoint' => 'pre_assembly',
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
            'checkpoint' => 'pre_assembly',
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
            'checkpoint' => 'pre_assembly',
            'image' => UploadedFile::fake()->create('inspection.jpg', 50, 'image/jpeg'),
            'defects' => [
                ['defect_type' => 'scratch', 'confidence_score' => 1.5],
            ],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('defects.0.confidence_score');
    }

    public function test_it_auto_completes_the_batch_once_produced_pieces_reach_the_expected_count(): void
    {
        $service = User::factory()->create(['role' => 'system_admin']);
        Sanctum::actingAs($service, ['inspections:create']);

        $batch = Batch::create([
            'batch_code' => 'THRESHOLD-001',
            'production_date' => now(),
            'manufacturing_stage' => 'pre_assembly',
            'expected_pieces' => 2,
        ]);

        $this->postJson('/api/inspections', [
            'batch_id' => $batch->id,
            'checkpoint' => 'pre_assembly',
            'component_type' => 'component_a',
            'image' => $this->fakeImageWithContent('one.png'),
        ])->assertCreated();

        $this->assertSame('open', $batch->fresh()->status);

        $this->postJson('/api/inspections', [
            'batch_id' => $batch->id,
            'checkpoint' => 'pre_assembly',
            'component_type' => 'component_a',
            'image' => $this->fakeImageWithContent('two.png'),
        ])->assertCreated();

        $this->assertSame('completed', $batch->fresh()->status);
    }

    public function test_it_requires_a_component_type_at_the_pre_assembly_checkpoint(): void
    {
        $service = User::factory()->create(['role' => 'system_admin']);
        Sanctum::actingAs($service, ['inspections:create']);

        $batch = $this->makeBatch();

        $response = $this->postJson('/api/inspections', [
            'batch_id' => $batch->id,
            'checkpoint' => 'pre_assembly',
            'image' => $this->fakeImageWithContent(),
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('component_type');
    }

    public function test_it_rejects_an_invalid_component_type(): void
    {
        $service = User::factory()->create(['role' => 'system_admin']);
        Sanctum::actingAs($service, ['inspections:create']);

        $batch = $this->makeBatch();

        $response = $this->postJson('/api/inspections', [
            'batch_id' => $batch->id,
            'checkpoint' => 'pre_assembly',
            'component_type' => 'not-a-real-component',
            'image' => $this->fakeImageWithContent(),
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('component_type');
    }

    public function test_it_does_not_require_a_component_type_at_the_preparation_checkpoint(): void
    {
        $service = User::factory()->create(['role' => 'system_admin']);
        Sanctum::actingAs($service, ['inspections:create']);

        $batch = Batch::create([
            'batch_code' => 'API-'.fake()->unique()->numerify('###'),
            'production_date' => now(),
            'manufacturing_stage' => 'preparation',
        ]);

        $response = $this->postJson('/api/inspections', [
            'batch_id' => $batch->id,
            'checkpoint' => 'preparation',
            'image' => $this->fakeImageWithContent(),
        ]);

        $response->assertCreated();
    }

    public function test_it_creates_an_inspection_with_no_defects(): void
    {
        $service = User::factory()->create(['role' => 'system_admin']);
        Sanctum::actingAs($service, ['inspections:create']);

        $batch = $this->makeBatch();

        $response = $this->postJson('/api/inspections', [
            'batch_id' => $batch->id,
            'checkpoint' => 'preparation',
            'image' => $this->fakeImageWithContent('clean.png'),
        ]);

        $response->assertCreated();
        $response->assertJson(['defect_count' => 0]);
    }
}
