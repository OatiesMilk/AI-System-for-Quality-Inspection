<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Inspection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InspectionImageTest extends TestCase
{
    use RefreshDatabase;

    private function makeInspectionWithImage(?string $imageData = 'ZmFrZS1pbWFnZS1ieXRlcw=='): Inspection
    {
        $batch = Batch::create([
            'batch_code' => 'IMG-'.fake()->unique()->numerify('###'),
            'production_date' => now(),
            'manufacturing_stage' => 'pre_assembly',
        ]);

        return Inspection::create([
            'batch_id' => $batch->id,
            'checkpoint' => 'pre_assembly',
            'image_data' => $imageData,
            'image_mime' => 'image/jpeg',
        ]);
    }

    public function test_guests_cannot_view_an_inspection_image(): void
    {
        $inspection = $this->makeInspectionWithImage();

        $response = $this->get(route('inspections.image', $inspection));

        $response->assertRedirect('/login');
    }

    public function test_any_authenticated_role_can_view_an_inspection_image(): void
    {
        $inspection = $this->makeInspectionWithImage();
        $constructor = User::factory()->create(['role' => 'shoe_constructor']);

        $response = $this->actingAs($constructor)->get(route('inspections.image', $inspection));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/jpeg');
        $this->assertSame('fake-image-bytes', $response->getContent());
    }

    public function test_it_returns_404_when_no_image_is_stored(): void
    {
        $inspection = $this->makeInspectionWithImage(null);
        $inspector = User::factory()->create(['role' => 'quality_inspector']);

        $response = $this->actingAs($inspector)->get(route('inspections.image', $inspection));

        $response->assertNotFound();
    }
}
