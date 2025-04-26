<?php

return [
    'provider' => env('AGENT_PROVIDER', 'openai'),
    'model' => env('AGENT_MODEL', 'o4-mini'),
    'pricing' => [
        'input' => env('AGENT_PRICING_INPUT_PRICE', 1.1),
        'output' => env('AGENT_PRICING_OUTPUT_PRICE', 4.4),
    ],
];
