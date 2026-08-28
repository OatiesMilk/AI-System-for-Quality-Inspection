<?php

namespace Database\Factories;

use App\Models\Batch;
use App\Models\Inspection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inspection>
 */
class InspectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'batch_id' => Batch::factory(),
            'checkpoint' => fake()->randomElement(['preparation', 'pre_assembly']),
            'image_path' => null,
            'action' => null,
            'ai_override' => false,
            'inspector_id' => null,
            'inspected_at' => null,
        ];
    }

    /**
     * Indicate that the inspection has already been reviewed by an inspector.
     */
    public function reviewed(string $action = 'pass', bool $aiOverride = false): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => $action,
            'ai_override' => $aiOverride,
            'inspected_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }
}
