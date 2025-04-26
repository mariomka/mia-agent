<?php

return [
    'prism_server' => [
        // The middleware that will be applied to the Prism Server routes.
        'middleware' => [],
        'enabled' => env('PRISM_SERVER_ENABLED', false),
    ],
    'providers' => [
        'openai' => [
            'url' => env('OPENAI_URL', 'https://api.openai.com/v1'),
            'api_key' => env('OPENAI_API_KEY', ''),
            'organization' => env('OPENAI_ORGANIZATION', null),
            'project' => env('OPENAI_PROJECT', null),
        ],
        'anthropic' => [
            'api_key' => env('ANTHROPIC_API_KEY', ''),
            'version' => env('ANTHROPIC_API_VERSION', '2023-06-01'),
            'default_thinking_budget' => env('ANTHROPIC_DEFAULT_THINKING_BUDGET', 1024),
            // Include beta strings as a comma separated list.
            'anthropic_beta' => env('ANTHROPIC_BETA', null),
        ],
        'ollama' => [
            'url' => env('OLLAMA_URL', 'http://localhost:11434'),
        ],
        'mistral' => [
            'api_key' => env('MISTRAL_API_KEY', ''),
            'url' => env('MISTRAL_URL', 'https://api.mistral.ai/v1'),
        ],
        'groq' => [
            'api_key' => env('GROQ_API_KEY', ''),
            'url' => env('GROQ_URL', 'https://api.groq.com/openai/v1'),
        ],
        'xai' => [
            'api_key' => env('XAI_API_KEY', ''),
            'url' => env('XAI_URL', 'https://api.x.ai/v1'),
        ],
        'gemini' => [
            'api_key' => env('GEMINI_API_KEY', ''),
            'url' => env('GEMINI_URL', 'https://generativelanguage.googleapis.com/v1beta/models'),
        ],
        'deepseek' => [
            'api_key' => env('DEEPSEEK_API_KEY', ''),
            'url' => env('DEEPSEEK_URL', 'https://api.deepseek.com/v1'),
        ],
        'voyageai' => [
            'api_key' => env('VOYAGEAI_API_KEY', ''),
            'url' => env('VOYAGEAI_URL', 'https://api.voyageai.com/v1'),
        ],
    ],

    // Interview agent configuration
    'interview_agent' => [
        'provider' => env('INTERVIEW_AGENT_PROVIDER', 'openai'),
        'model' => env('INTERVIEW_AGENT_MODEL', 'gpt-4.1-mini'),
    ],

    // Token pricing configuration per million tokens (in USD)
    'pricing' => [
        // Default fallback pricing for all providers and models
        'default' => [
            'input' => env('DEFAULT_TOKEN_INPUT_PRICE', 0.5),
            'output' => env('DEFAULT_TOKEN_OUTPUT_PRICE', 1.5),
        ],
        'openai' => [
            // Default pricing for all OpenAI models
            'default' => [
                'input' => env('OPENAI_DEFAULT_INPUT_PRICE', 0.5),
                'output' => env('OPENAI_DEFAULT_OUTPUT_PRICE', 1.5),
            ],
            // Model-specific pricing (will override defaults when specified)
            'o4-mini' => [
                'input' => env('OPENAI_O4_MINI_INPUT_PRICE', 1.10),
                'output' => env('OPENAI_O4_MINI_OUTPUT_PRICE', 4.40),
            ],
            'gpt-4.1-mini' => [
                'input' => env('OPENAI_GPT_4_1_MINI_INPUT_PRICE', 0.40),
                'output' => env('OPENAI_GPT_4_1_MINI_OUTPUT_PRICE', 1.60),
            ],
        ],
        'deepseek' => [
            // Default pricing for all DeepSeek models
            'default' => [
                'input' => env('DEEPSEEK_DEFAULT_INPUT_PRICE', 1.0),
                'output' => env('DEEPSEEK_DEFAULT_OUTPUT_PRICE', 2.0),
            ],
            // Model-specific pricing
            'deepseek-chat' => [
                'input' => env('DEEPSEEK_CHAT_INPUT_PRICE', 1.10),
                'output' => env('DEEPSEEK_CHAT_OUTPUT_PRICE', 2.19),
            ],
        ],
        'anthropic' => [
            // Default pricing for all Anthropic models
            'default' => [
                'input' => env('ANTHROPIC_DEFAULT_INPUT_PRICE', 3.0),
                'output' => env('ANTHROPIC_DEFAULT_OUTPUT_PRICE', 15.0),
            ],
        ],
    ],
];
