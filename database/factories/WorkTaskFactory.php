<?php

namespace Database\Factories;

use App\Models\Call;
use App\Models\ResolutionType;
use App\Models\WorkTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WorkTask> */
class WorkTaskFactory extends Factory
{
    protected $model = WorkTask::class;

    public function definition(): array
    {
        return [
            'call_id' => Call::factory(),
            'resolution_type_id' => fake()->optional(0.85)->randomElement(
                ResolutionType::pluck('id')->toArray() ?: [null]
            ),
            'work_completed_at' => fake()->optional(0.5)->dateTimeBetween('-3 months', 'now'),
        ];
    }
}
