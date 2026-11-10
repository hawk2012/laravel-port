<?php

return [

    'default_credits' => (int) env('BILLING_DEFAULT_CREDITS', 500),

    'credits' => [
        'image' => 5,
        'video' => 40,
        'audio' => 10,
        'text' => 1,
    ],

];
