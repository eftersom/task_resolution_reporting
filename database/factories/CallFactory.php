<?php

namespace Database\Factories;

use App\Models\Call;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Call> */
class CallFactory extends Factory
{
    protected $model = Call::class;

    public function definition(): array
    {
        return [
            'notes' => fake()->optional(0.6)->paragraph(),
            'stage' => fake()->randomElement([
                'open',
                'in-progress',
                'complete',
                'pending',
                'draft',
                'archived',
            ]),
        ];
    }
}
