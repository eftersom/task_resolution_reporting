<?php

use App\Models\Call;
use App\Models\ResolutionType;
use App\Models\WorkTask;

function createTask(
    string $stage = 'open',
    ?int $resolutionTypeId = null,
    ?string $createdAt = null,
): WorkTask {
    $call = Call::factory()->create(['stage' => $stage]);

    return WorkTask::factory()->create([
        'call_id' => $call->id,
        'resolution_type_id' => $resolutionTypeId,
        'created_at' => $createdAt ?? now(),
    ]);
}

describe('hasResolutionType scope is being applied correctly', function () {

    it('includes tasks that have a resolution type', function () {
        $type = ResolutionType::factory()->create();

        createTask('open', $type->id);

        expect(WorkTask::hasResolutionType()->count())->toBe(1);
    });

    it('excludes tasks without a resolution type', function () {
        createTask('open', null);

        expect(WorkTask::hasResolutionType()->count())->toBe(0);
    });
});

describe('createdBetween scope is being applied correctly', function () {

    it('includes tasks created within the range', function () {
        $type = ResolutionType::factory()->create();

        createTask('open', $type->id, '2026-03-15 12:00:00');

        expect(WorkTask::createdBetween('2026-03-01', '2026-03-31')->count())->toBe(1);
    });

    it('includes tasks created at the start boundary', function () {
        $type = ResolutionType::factory()->create();

        createTask('open', $type->id, '2026-03-01 00:00:00');

        expect(WorkTask::createdBetween('2026-03-01', '2026-03-31')->count())->toBe(1);
    });

    it('includes tasks created at the end boundary', function () {
        $type = ResolutionType::factory()->create();

        createTask('open', $type->id, '2026-03-31 23:59:59');

        expect(WorkTask::createdBetween('2026-03-01', '2026-03-31')->count())->toBe(1);
    });

    it('excludes tasks created before the range', function () {
        $type = ResolutionType::factory()->create();

        createTask('open', $type->id, '2026-02-28 23:59:59');

        expect(WorkTask::createdBetween('2026-03-01', '2026-03-31')->count())->toBe(0);
    });

    it('excludes tasks created after the range', function () {
        $type = ResolutionType::factory()->create();

        createTask('open', $type->id, '2026-04-01 00:00:00');

        expect(WorkTask::createdBetween('2026-03-01', '2026-03-31')->count())->toBe(0);
    });
});

describe('withActiveCall scope is being applied correctly', function () {

    it('includes tasks with active call stages', function () {
        $type = ResolutionType::factory()->create();

        foreach (['open', 'in-progress', 'complete', 'pending'] as $stage) {
            createTask($stage, $type->id);
        }

        expect(WorkTask::withActiveCall()->count())->toBe(4);
    });

    it('excludes tasks with any excluded draft calls', function () {
        $type = ResolutionType::factory()->create();

        createTask('draft', $type->id);

        expect(WorkTask::withActiveCall()->count())->toBe(0);
    });

    it('excludes tasks with any excluded archived calls', function () {
        $type = ResolutionType::factory()->create();

        createTask('archived', $type->id);

        expect(WorkTask::withActiveCall()->count())->toBe(0);
    });
});
