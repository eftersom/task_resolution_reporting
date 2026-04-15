<?php

namespace App\Transformers;

use App\Models\ResolutionType;

class ResolutionTypeSummaryTransformer
{
    /**
     * transform resolution type summary data, passing ONLY the data we need to the client
     *
     * @param ResolutionType $resolutionType
     * @return array
     */
    public static function transform(ResolutionType $resolutionType): array
    {
        return [
            'id' => $resolutionType->id,
            'name' => $resolutionType->name,
            'description' => $resolutionType->description,
            'count' => $resolutionType->count,
        ];
    }
}
