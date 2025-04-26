<?php

return [
    'provider' => env('AGENT_PROVIDER'),
    'model' => env('AGENT_MODEL'),
    'pricing' => [
        'input' => (int) env('AGENT_PRICING_INPUT_PRICE'),
        'output' => (int) env('AGENT_PRICING_OUTPUT_PRICE'),
    ],
];
