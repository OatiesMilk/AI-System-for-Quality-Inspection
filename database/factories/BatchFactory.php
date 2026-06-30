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
        $shift = fake()->randomElement(['am', 'pm']);
        $batchNumber = $shift === 'am' ? 1 : 2;

        return [
            'batch_code' => 'BATCH-'.$batchNumber.'-'.fake()->unique()->numerify('########'),
            'production_date' => fake()->dateTimeBetween('-2 weeks', 'now'),
            'shift' => $shift,
            'manufacturing_stage' => fake()->randomElement(['preparation', 'finishing']),
        ];
    }
}
