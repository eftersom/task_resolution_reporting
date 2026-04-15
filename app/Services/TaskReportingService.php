<?php

namespace App\Services;

use App\Models\ResolutionType;
use App\Models\WorkTask;
use App\Transformers\ResolutionTypeSummaryTransformer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class TaskReportingService
{
    /**
     * get and cache resolution type summary for date range
     *
     * @param string $start
     * @param string $until
     * @return array
     */
    public function getResolutionTypeSummary(string $start, string $until): array
    {
        return Cache::remember(
            "reporting:tasks:res:{$start}:{$until}",
            config('reporting.cache_ttl'),
            fn () => $this->buildSummary($start, $until)
        );
    }

    /**
     * build resolution type summary for date range
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    private function buildSummary(string $startDate, string $endDate): array
    {
        $counts = $this->countTasks($startDate, $endDate);
        $resolutionTypes = $this->getResolutionTypes($counts);

        return [
            'resolution_types' => $resolutionTypes
                ->map(fn (ResolutionType $type) => ResolutionTypeSummaryTransformer::transform($type))
                ->all(),
            'total_tasks' => $counts->sum(),
        ];
    }

    /**
     * count tasks by resolution type for date range
     *
     * @param string $startDate
     * @param string $endDate
     * @return Collection
     */
    private function countTasks(string $startDate, string $endDate): Collection
    {
        return WorkTask::query()
            ->hasResolutionType()
            ->createdBetween($startDate, $endDate)
            ->withActiveCall()
            ->selectRaw('resolution_type_id, count(*) as count')
            ->groupBy('resolution_type_id')
            ->pluck('count', 'resolution_type_id');
    }

    /**
     * get resolution types with counts
     *
     * @param Collection $counts
     * @return Collection
     */
    private function getResolutionTypes(Collection $counts): Collection
    {
        if ($counts->isEmpty()) {
            return collect();
        }

        return ResolutionType::query()
            ->whereIn('id', $counts->keys())
            ->get()
            ->each(function (ResolutionType $type) use ($counts) {
                $type->count = $counts[$type->id];
            })
            ->sortByDesc('count')
            ->values();
    }
}
