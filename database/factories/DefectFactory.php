<?php

namespace Database\Factories;

use App\Models\Defect;
use App\Models\Inspection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Defect>
 */
class DefectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inspection_id' => Inspection::factory(),
            'defect_type' => fake()->randomElement([
                'scratch', 'cut', 'hole', 'crease', 'glue', 'stitch',
            ]),
            'confidence_score' => fake()->randomFloat(4, 0.55, 0.99),
            'bounding_box' => [
                'x' => fake()->randomFloat(2, 0.05, 0.7),
                'y' => fake()->randomFloat(2, 0.05, 0.7),
                'width' => fake()->randomFloat(2, 0.05, 0.2),
                'height' => fake()->randomFloat(2, 0.05, 0.2),
            ],
            'confirmed' => null,
        ];
    }
}
