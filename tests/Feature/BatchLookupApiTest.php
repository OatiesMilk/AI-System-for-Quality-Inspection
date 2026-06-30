<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BatchLookupApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $response = $this->getJson('/api/batches/latest?checkpoint=preparation');

        $response->assertUnauthorized();
    }

    public function test_it_resolves_the_most_recently_created_matching_batch(): void
    {
        $service = User::factory()->create(['role' => 'system_admin']);
        Sanctum::actingAs($service, ['inspections:create']);

        $older = Batch::create([
            'batch_code' => 'OLDER-001',
            'production_date' => now()->subDay(),
            'shift' => 'am',
            'manufacturing_stage' => 'preparation',
        ]);

        $newer = Batch::create([
            'batch_code' => 'NEWER-001',
            'production_date' => now(),
            'shift' => 'am',
            'manufacturing_stage' => 'preparation',
        ]);

        $response = $this->getJson('/api/batches/latest?checkpoint=preparation');

        $response->assertOk();
        $response->assertJson([
            'batch_id' => $newer->id,
            'batch_code' => 'NEWER-001',
        ]);
    }

    public function test_it_filters_by_shift_when_provided(): void
    {
        $service = User::factory()->create(['role' => 'system_admin']);
        Sanctum::actingAs($service, ['inspections:create']);

        Batch::create([
            'batch_code' => 'AM-BATCH',
            'production_date' => now(),
            'shift' => 'am',
            'manufacturing_stage' => 'finishing',
        ]);

        $pmBatch = Batch::create([
            'batch_code' => 'PM-BATCH',
            'production_date' => now(),
            'shift' => 'pm',
            'manufacturing_stage' => 'finishing',
        ]);

        $response = $this->getJson('/api/batches/latest?checkpoint=finishing&shift=pm');

        $response->assertOk();
        $response->assertJson(['batch_id' => $pmBatch->id]);
    }

    public function test_it_returns_404_when_no_batch_matches(): void
    {
        $service = User::factory()->create(['role' => 'system_admin']);
        Sanctum::actingAs($service, ['inspections:create']);

        $response = $this->getJson('/api/batches/latest?checkpoint=finishing');

        $response->assertNotFound();
    }

    public function test_it_rejects_an_invalid_checkpoint(): void
    {
        $service = User::factory()->create(['role' => 'system_admin']);
        Sanctum::actingAs($service, ['inspections:create']);

        $response = $this->getJson('/api/batches/latest?checkpoint=not-a-real-stage');

        $response->assertUnprocessable();
    }
}
