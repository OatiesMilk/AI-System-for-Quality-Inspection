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

    public function test_it_resolves_the_oldest_open_matching_batch_as_a_fifo_queue(): void
    {
        $service = User::factory()->create(['role' => 'system_admin']);
        Sanctum::actingAs($service, ['inspections:create']);

        $older = Batch::create([
            'batch_code' => 'OLDER-001',
            'production_date' => now()->subDay(),
            'manufacturing_stage' => 'preparation',
        ]);

        Batch::create([
            'batch_code' => 'NEWER-001',
            'production_date' => now(),
            'manufacturing_stage' => 'preparation',
        ]);

        $response = $this->getJson('/api/batches/latest?checkpoint=preparation');

        $response->assertOk();
        $response->assertJson([
            'batch_id' => $older->id,
            'batch_code' => 'OLDER-001',
        ]);
    }

    public function test_it_skips_completed_batches_and_falls_through_to_the_next_open_one(): void
    {
        $service = User::factory()->create(['role' => 'system_admin']);
        Sanctum::actingAs($service, ['inspections:create']);

        Batch::create([
            'batch_code' => 'DONE-001',
            'production_date' => now()->subDay(),
            'manufacturing_stage' => 'preparation',
            'status' => 'completed',
        ]);

        $stillOpen = Batch::create([
            'batch_code' => 'OPEN-001',
            'production_date' => now(),
            'manufacturing_stage' => 'preparation',
            'status' => 'open',
        ]);

        $response = $this->getJson('/api/batches/latest?checkpoint=preparation');

        $response->assertOk();
        $response->assertJson(['batch_id' => $stillOpen->id]);
    }

    public function test_it_returns_404_when_no_batch_matches(): void
    {
        $service = User::factory()->create(['role' => 'system_admin']);
        Sanctum::actingAs($service, ['inspections:create']);

        $response = $this->getJson('/api/batches/latest?checkpoint=pre_assembly');

        $response->assertNotFound();
    }

    public function test_it_rejects_an_invalid_checkpoint(): void
    {
        $service = User::factory()->create(['role' => 'system_admin']);
        Sanctum::actingAs($service, ['inspections:create']);

        $response = $this->getJson('/api/batches/latest?checkpoint=not-a-real-stage');

        $response->assertUnprocessable();
    }

    public function test_the_operator_can_close_the_batch_they_are_working(): void
    {
        $service = User::factory()->create(['role' => 'system_admin']);
        Sanctum::actingAs($service, ['inspections:create']);

        $batch = Batch::create([
            'batch_code' => 'OP-CLOSE-001',
            'production_date' => now(),
            'expected_pieces' => 40,
            'manufacturing_stage' => 'preparation',
        ]);

        $response = $this->patchJson("/api/batches/{$batch->id}/close");

        $response->assertOk();
        $response->assertJson([
            'batch_id' => $batch->id,
            'status' => 'completed',
            'produced' => 0,
        ]);

        $this->assertSame('completed', $batch->fresh()->status);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'batch.closed',
            'user_id' => $service->id,
        ]);
    }

    public function test_closing_a_batch_immediately_advances_the_queue_to_the_next_open_batch(): void
    {
        $service = User::factory()->create(['role' => 'system_admin']);
        Sanctum::actingAs($service, ['inspections:create']);

        $current = Batch::create([
            'batch_code' => 'QUEUE-A',
            'production_date' => now()->subDay(),
            'manufacturing_stage' => 'preparation',
        ]);

        $next = Batch::create([
            'batch_code' => 'QUEUE-B',
            'production_date' => now(),
            'manufacturing_stage' => 'preparation',
        ]);

        $this->patchJson("/api/batches/{$current->id}/close")->assertOk();

        $response = $this->getJson('/api/batches/latest?checkpoint=preparation');

        $response->assertOk();
        $response->assertJson(['batch_id' => $next->id]);
    }
}
