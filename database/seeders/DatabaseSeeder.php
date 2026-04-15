<?php

namespace Database\Seeders;

use App\Models\Call;
use App\Models\ResolutionType;
use App\Models\User;
use App\Models\WorkTask;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $resolutionTypes = collect([
            ['name' => 'In-progress - Awaiting Purchase Order from Customer', 'description' => 'Awaiting purchase order from customer'],
            ['name' => 'In-progress - Parts On Order', 'description' => 'Replacement parts have been ordered'],
            ['name' => 'In-progress - Referred to Manufacturer', 'description' => null],
            ['name' => 'In-progress - Scheduled Follow-Up', 'description' => 'A follow up visit has been booked'],
            ['name' => 'In-progress - Warranty Claim Filed', 'description' => null],
            ['name' => 'Fix Complete - Collection Arranged', 'description' => 'Parts collection arranged with courier'],
            ['name' => 'Fix Complete - Parts Collection Required', 'description' => 'Parts to be collected from site or customer'],
            ['name' => 'Incomplete - Escalated', 'description' => 'Requires a senior technician to resolve'],
        ])->map(fn ($data) => ResolutionType::create($data));

        $resolutionTypeIds = $resolutionTypes->pluck('id')->toArray();

        $dateRanges = [
            ['from' => '2026-01-01', 'to' => '2026-01-31'],
            ['from' => '2026-02-01', 'to' => '2026-02-28'],
            ['from' => '2026-03-01', 'to' => '2026-03-31'],
            ['from' => '2026-04-01', 'to' => '2026-04-14'],
        ];

        $stages = [
            'open'        => 15,
            'in-progress' => 15,
            'complete'    => 15,
            'pending'     => 15,
            'draft'       => 10,
            'archived'    => 10,
        ];

        foreach ($stages as $stage => $count) {
            $calls = Call::factory()->count($count)->create(['stage' => $stage]);

            foreach ($calls as $call) {
                $range = fake()->randomElement($dateRanges);

                WorkTask::factory()->create([
                    'call_id' => $call->id,
                    'resolution_type_id' => fake()->optional(0.85)->randomElement($resolutionTypeIds),
                    'created_at' => fake()->dateTimeBetween($range['from'], $range['to']),
                ]);
            }
        }
    }
}
