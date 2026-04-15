<?php

use App\Models\ResolutionType;
use App\Transformers\ResolutionTypeSummaryTransformer;

it('transforms resolution types into expected array shape', function () {
    $type = new ResolutionType([
        'name' => 'Parts On Order',
        'description' => 'Replacement parts have been ordered',
    ]);
    
    $type->id = 42;
    $type->count = 7;

    $result = ResolutionTypeSummaryTransformer::transform($type);

    expect($result)->toBe([
        'id' => 42,
        'name' => 'Parts On Order',
        'description' => 'Replacement parts have been ordered',
        'count' => 7,
    ]);
});

it('only exposes id, name, description and count elements', function () {
    $type = new ResolutionType([
        'name' => 'Fix Completed',
        'description' => 'The issue has been resolved',
    ]);

    $type->id = 1;
    $type->count = 3;

    $result = ResolutionTypeSummaryTransformer::transform($type);

    expect(array_keys($result))->toEqual(['id', 'name', 'description', 'count']);
    expect($result)->not->toHaveKeys(['created_at', 'updated_at']);
});

it('handles any null description values', function () {
    $type = new ResolutionType([
        'name' => 'Escalated',
        'description' => null,
    ]);

    $type->id = 5;
    $type->count = 2;

    $result = ResolutionTypeSummaryTransformer::transform($type);

    expect($result['description'])->toBeNull();
    expect($result['count'])->toBe(2);
});
