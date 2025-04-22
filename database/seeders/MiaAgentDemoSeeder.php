<?php

namespace Database\Seeders;

use App\Models\Interview;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MiaAgentDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Mia Agent Demo Interview
        Interview::factory()->create([
            'id' => Str::uuid()->toString(),
            'name' => 'Mia Agent Demo Interview',
            'language' => 'english',
            'agent_name' => 'Mia',
            'interview_type' => 'Market Research',
            'target_name' => 'Mia Agent',
            'target_description' => 'Mia Agent is an AI-powered interview platform that automates various types of interviews including market research, customer feedback, product validation, and more. The system creates consistent interview experiences while gathering valuable insights for stakeholders across different domains and use cases.',
            'topics' => [
                [
                    'key' => Str::random(10),
                    'question' => 'What do you think about AI-powered interview systems like Mia Agent?',
                    'description' => 'Gauge general perception about AI interview systems',
                    'approach' => 'direct'
                ],
                [
                    'key' => Str::random(10),
                    'question' => 'How do you think AI-powered interviews compare to traditional human-led interviews?',
                    'description' => 'Understand perceived differences between AI and human interviewers',
                    'approach' => 'direct'
                ],
                [
                    'key' => Str::random(10),
                    'question' => 'What types of interviews do you think AI could be most effective at conducting?',
                    'description' => 'Identify perceived strengths across different interview types',
                    'approach' => 'direct'
                ],
                [
                    'key' => Str::random(10),
                    'question' => 'What potential limitations or challenges do you see with AI conducting interviews?',
                    'description' => 'Uncover perceived limitations of AI interview systems',
                    'approach' => 'direct'
                ],
                [
                    'key' => Str::random(10),
                    'question' => 'How comfortable would you feel participating in an AI-led interview for market research?',
                    'description' => 'Assess comfort with AI interviewers in non-recruitment contexts',
                    'approach' => 'direct'
                ],
                [
                    'key' => Str::random(10),
                    'question' => 'What features would make you trust an AI interviewer more?',
                    'description' => 'Identify trust-building elements for AI interview systems',
                    'approach' => 'direct'
                ]
            ],
        ]);
    }
} 