<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskResolutionReportRequest;
use App\Services\TaskReportingService;
use Illuminate\Http\JsonResponse;

class TaskReportingController extends Controller
{
    public function __construct(
        private readonly TaskReportingService $reportingService,
    ) {}

    /**
     * Get the resolution type summary for date range
     *
     * @param TaskResolutionReportRequest $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function resolutionTypeSummary(TaskResolutionReportRequest $request): JsonResponse
    {
        $startDate = $request->validated('startDate');
        $endDate = $request->validated('endDate');

        $result = $this->reportingService->getResolutionTypeSummary($startDate, $endDate);

        return response()->json([
            'data' => $result['resolution_types'],
            'meta' => [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'total_tasks' => $result['total_tasks'],
            ],
        ]);
    }
}
