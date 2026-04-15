<?php

use App\Http\Controllers\Api\TaskReportingController;
use Illuminate\Support\Facades\Route;

Route::prefix('reports')
    ->middleware('throttle:30,1')
    ->group(function () {
        Route::get('work-tasks/resolutions', [TaskReportingController::class, 'resolutionTypeSummary']);
    });
