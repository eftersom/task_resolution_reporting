<?php

return [

    'cache_ttl' => env('REPORTING_CACHE_TTL', 60),

    'max_range_days' => 366,

    'allowed_params' => ['from', 'to'],

    'excluded_stages' => ['draft', 'archived'],

];
