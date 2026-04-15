<?php

return [

    'cache_ttl' => env('REPORTING_CACHE_TTL', 60),

    'max_range_days' => 366,

    'allowed_params' => ['startDate', 'endDate'],

    'excluded_stages' => ['draft', 'archived'],

];
