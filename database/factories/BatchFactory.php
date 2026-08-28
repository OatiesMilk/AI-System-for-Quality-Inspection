<?php

namespace Database\Factories;

use App\Models\Batch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Batch>
 */
class BatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'batch_code' => 'BATCH-'.fake()->unique()->numerify('########'),
            'production_date' => fake()->dateTimeBetween('-2 weeks', 'now'),
            'expected_pieces' => fake()->numberBetween(20, 60),
            'manufacturing_stage' => fake()->randomElement(['preparation', 'finishing']),
        ];
    }
}
