<?php

namespace Database\Factories;

use App\Models\ResolutionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ResolutionType> */
class ResolutionTypeFactory extends Factory
{
    protected $model = ResolutionType::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Fix Completed',
                'Awaiting Purchase Order',
                'Parts On Order',
                'Referred to Manufacturer',
                'Customer No Access',
                'Scheduled Follow-Up',
                'Warranty Claim Filed',
                'Escalated to Senior Tech',
            ]),
            'description' => fake()->optional(0.7)->sentence(),
        ];
    }
}
