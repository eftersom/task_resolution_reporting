<?php

use App\Models\Call;
use App\Models\ResolutionType;
use App\Models\WorkTask;
use Illuminate\Support\Facades\Cache;

const ENDPOINT = '/api/reporting/work-tasks/resolutions';

function createTaskWithCall(
    string $callStage = 'open',
    ?ResolutionType $resolutionType = null,
    ?string $createdAt = null,
): WorkTask {
    $call = Call::factory()->create(['stage' => $callStage]);

    return WorkTask::factory()->create([
        'call_id' => $call->id,
        'resolution_type_id' => $resolutionType?->id,
        'created_at' => $createdAt ?? now(),
    ]);
}

describe('validation', function () {

    it('requires startDate', function () {
        $this->getJson(ENDPOINT.'?endDate=2026-04-01')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['startDate']);
    });

    it('requires endDate', function () {
        $this->getJson(ENDPOINT.'?startDate=2026-01-01')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['endDate']);
    });

    it('requires both parameters', function () {
        $this->getJson(ENDPOINT)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['startDate', 'endDate']);
    });

    it('rejects invalid startDate', function () {
        $this->getJson(ENDPOINT.'?startDate=01-01-2026&endDate=2026-04-01')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['startDate']);
    });

    it('rejects invalid endDate', function () {
        $this->getJson(ENDPOINT.'?startDate=2026-01-01&endDate=01-04-2026')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['endDate']);
    });

    it('rejects endDate if before startDate', function () {
        $this->getJson(ENDPOINT.'?startDate=2026-06-01&endDate=2026-01-01')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['endDate']);
    });

    it('rejects a date range of more than 366 days', function () {
        $this->getJson(ENDPOINT.'?startDate=2025-01-01&endDate=2026-04-01')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['endDate']);
    });

    it('accepts a date range of exactly 366 days', function () {
        $this->getJson(ENDPOINT.'?startDate=2025-01-01&endDate=2026-01-02')
            ->assertStatus(200);
    });

    it('rejects unexpected and unwanted query parameters', function () {
        $this->getJson(ENDPOINT.'?startDate=2026-01-01&endDate=2026-04-01&extra=nobueno')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['parameters']);
    });

    it('returns custom validation messages for invalid dates', function () {
        $response = $this->getJson(ENDPOINT.'?startDate=bad&endDate=bad');

        $response->assertStatus(422)
            ->assertJsonFragment(['Start date must be in YYYY-MM-DD format.'])
            ->assertJsonFragment(['End date must be in YYYY-MM-DD format.']);
    });
});

describe('response structure is correct', function () {

    it('returns expected primary elements', function () {
        $this->getJson(ENDPOINT.'?startDate=2026-01-01&endDate=2026-04-01')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta']);
    });

    it('returns expected meta data', function () {
        $this->getJson(ENDPOINT.'?startDate=2026-01-01&endDate=2026-04-01')
            ->assertStatus(200)
            ->assertJsonStructure([
                'meta' => ['startDate', 'endDate', 'total_tasks'],
            ]);
    });

    it('echoes the requested date range in meta', function () {
        $this->getJson(ENDPOINT.'?startDate=2026-02-01&endDate=2026-03-15')
            ->assertStatus(200)
            ->assertJsonPath('meta.startDate', '2026-02-01')
            ->assertJsonPath('meta.endDate', '2026-03-15');
    });

    it('returns expected fields for each resolution type', function () {
        $type = ResolutionType::factory()->create();

        createTaskWithCall('open', $type, '2026-03-01');

        $this->getJson(ENDPOINT.'?startDate=2026-01-01&endDate=2026-04-01')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    ['id', 'name', 'description', 'count'],
                ],
            ]);
    });

    it('does not expose extra or unexpected attributes in returned data', function () {
        $type = ResolutionType::factory()->create();
        createTaskWithCall('open', $type, '2026-03-01');

        $response = $this->getJson(ENDPOINT.'?startDate=2026-01-01&endDate=2026-04-01');
        $item = $response->json('data.0');

        expect(array_keys($item))->toBe(['id', 'name', 'description', 'count']);
    });
});

describe('filters are applied correctly', function () {

    it('excludes tasks linked to any excluded draft calls', function () {
        $type = ResolutionType::factory()->create();
        createTaskWithCall('draft', $type, '2026-03-01');
        createTaskWithCall('open', $type, '2026-03-01');

        $response = $this->getJson(ENDPOINT.'?startDate=2026-01-01&endDate=2026-04-01');

        $response->assertStatus(200)->assertJsonPath('meta.total_tasks', 1);
    });

    it('excludes tasks linked to any excluded archived calls', function () {
        $type = ResolutionType::factory()->create();
        createTaskWithCall('archived', $type, '2026-03-01');
        createTaskWithCall('open', $type, '2026-03-01');

        $response = $this->getJson(ENDPOINT.'?startDate=2026-01-01&endDate=2026-04-01');

        $response->assertStatus(200)->assertJsonPath('meta.total_tasks', 1);
    });

    it('excludes tasks without any resolution type', function () {
        $type = ResolutionType::factory()->create();
        createTaskWithCall('open', $type, '2026-03-01');
        createTaskWithCall('open', null, '2026-03-01');

        $response = $this->getJson(ENDPOINT.'?startDate=2026-01-01&endDate=2026-04-01');

        $response->assertStatus(200)->assertJsonPath('meta.total_tasks', 1);
    });

    it('only includes tasks within requested date range', function () {
        $type = ResolutionType::factory()->create();
        createTaskWithCall('open', $type, '2026-03-15');
        createTaskWithCall('open', $type, '2025-12-31'); // before requested range
        createTaskWithCall('open', $type, '2026-05-01'); // and after requested range

        $response = $this->getJson(ENDPOINT.'?startDate=2026-01-01&endDate=2026-04-01');

        $response->assertStatus(200)->assertJsonPath('meta.total_tasks', 1);
    });

    it('includes all tasks on the boundary dates of requested range', function () {
        $type = ResolutionType::factory()->create();
        createTaskWithCall('open', $type, '2026-01-01 00:00:00');
        createTaskWithCall('open', $type, '2026-04-01 23:59:59');

        $response = $this->getJson(ENDPOINT.'?startDate=2026-01-01&endDate=2026-04-01');

        $response->assertStatus(200)->assertJsonPath('meta.total_tasks', 2);
    });

    it('includes all tasks with any non excluded call stage', function () {
        $type = ResolutionType::factory()->create();

        foreach (['open', 'in-progress', 'complete', 'pending'] as $stage) {
            createTaskWithCall($stage, $type, '2026-03-01');
        }

        $response = $this->getJson(ENDPOINT.'?startDate=2026-01-01&endDate=2026-04-01');

        $response->assertStatus(200)->assertJsonPath('meta.total_tasks', 4);
    });
});

describe('aggregation and sorting are being applied correctly', function () {

    it('returns proper expected counts for each resolution type', function () {
        $typeA = ResolutionType::factory()->create(['name' => 'Type A']);
        $typeB = ResolutionType::factory()->create(['name' => 'Type B']);

        createTaskWithCall('open', $typeA, '2026-03-01');
        createTaskWithCall('open', $typeA, '2026-03-02');
        createTaskWithCall('open', $typeA, '2026-03-03');
        createTaskWithCall('open', $typeB, '2026-03-01');
        createTaskWithCall('open', $typeB, '2026-03-02');

        $response = $this->getJson(ENDPOINT.'?startDate=2026-01-01&endDate=2026-04-01');
        $response->assertStatus(200);

        $data = collect($response->json('data'));

        expect($data->firstWhere('name', 'Type A')['count'])->toBe(3);
        expect($data->firstWhere('name', 'Type B')['count'])->toBe(2);
    });

    it('sorts results by count in desc listing', function () {
        $typeA = ResolutionType::factory()->create(['name' => 'Type A']);
        $typeB = ResolutionType::factory()->create(['name' => 'Type B']);

        createTaskWithCall('open', $typeA, '2026-03-01');
        createTaskWithCall('open', $typeB, '2026-03-01');
        createTaskWithCall('open', $typeB, '2026-03-02');
        createTaskWithCall('open', $typeB, '2026-03-03');
        createTaskWithCall('open', $typeB, '2026-03-04');
        createTaskWithCall('open', $typeB, '2026-03-05');

        $response = $this->getJson(ENDPOINT.'?startDate=2026-01-01&endDate=2026-04-01');

        $data = $response->json('data');

        expect($data[0]['name'])->toBe('Type B');
        expect($data[0]['count'])->toBe(5);
        expect($data[1]['name'])->toBe('Type A');
        expect($data[1]['count'])->toBe(1);
    });

    it('returns total tasks count as sum of all resolution type counts', function () {
        $typeA = ResolutionType::factory()->create();
        $typeB = ResolutionType::factory()->create();

        createTaskWithCall('open', $typeA, '2026-03-01');
        createTaskWithCall('open', $typeA, '2026-03-02');
        createTaskWithCall('open', $typeB, '2026-03-01');

        $response = $this->getJson(ENDPOINT.'?startDate=2026-01-01&endDate=2026-04-01');

        $response->assertJsonPath('meta.total_tasks', 3);
    });

    it('returns empty data if no tasks match the requested date range', function () {
        $response = $this->getJson(ENDPOINT.'?startDate=2026-01-01&endDate=2026-04-01');

        $response->assertStatus(200)->assertJsonPath('data', [])->assertJsonPath('meta.total_tasks', 0);
    });

    it('omits resolution types that have zero matching tasks', function () {
        ResolutionType::factory()->create(['name' => 'Unused Type']);
        $used = ResolutionType::factory()->create(['name' => 'Used Type']);

        createTaskWithCall('open', $used, '2026-03-01');

        $response = $this->getJson(ENDPOINT.'?startDate=2026-01-01&endDate=2026-04-01');

        $data = collect($response->json('data'));

        expect($data)->toHaveCount(1);
        expect($data->first()['name'])->toBe('Used Type');
    });
});

describe('caching is being applied as expected', function () {

    it('caches results for the configured TTL', function () {
        $type = ResolutionType::factory()->create();

        createTaskWithCall('open', $type, '2026-03-01');

        $this->getJson(ENDPOINT.'?startDate=2026-01-01&endDate=2026-04-01')
            ->assertJsonPath('meta.total_tasks', 1);

        expect(Cache::has('reporting:tasks:res:2026-01-01:2026-04-01'))->toBeTrue();
    });

    it('returns cached data even after underlying data changes', function () {
        $type = ResolutionType::factory()->create();

        createTaskWithCall('open', $type, '2026-03-01');

        $this->getJson(ENDPOINT.'?startDate=2026-01-01&endDate=2026-04-01')->assertJsonPath('meta.total_tasks', 1);

        createTaskWithCall('open', $type, '2026-03-02');

        $this->getJson(ENDPOINT.'?startDate=2026-01-01&endDate=2026-04-01')->assertJsonPath('meta.total_tasks', 1);
    });

    it('uses separate cache keys for different date ranges', function () {
        $type = ResolutionType::factory()->create();

        createTaskWithCall('open', $type, '2026-02-01');
        createTaskWithCall('open', $type, '2026-03-15');

        $this->getJson(ENDPOINT.'?startDate=2026-01-01&endDate=2026-02-28')->assertJsonPath('meta.total_tasks', 1);

        $this->getJson(ENDPOINT.'?startDate=2026-03-01&endDate=2026-04-01')->assertJsonPath('meta.total_tasks', 1);
    });
});
