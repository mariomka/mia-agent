<?php

namespace Database\Factories;

use App\Models\Interview;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Interview>
 */
class InterviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $languages = ['English', 'Spanish', 'French', 'German', 'Italian', 'Portuguese', 'Chinese', 'Japanese'];
        $agentNames = ['Mia', 'Alex', 'Sam', 'Jordan', 'Taylor', 'Riley', 'Casey', 'Morgan'];
        $interviewTypes = ['User Interview', 'Screening Interview', 'Customer Feedback', 'Market Research'];

        return [
            'name' => fake()->sentence(3),
            'interview_type' => fake()->randomElement($interviewTypes),
            'agent_name' => fake()->randomElement($agentNames),
            'language' => fake()->randomElement($languages),
            'target_name' => fake()->catchPhrase(),
            'target_description' => fake()->paragraph(),
            'topics' => [
                [
                    'key' => Str::random(10),
                    'question' => 'How do you currently use our application?',
                    'description' => 'Understand current usage patterns and workflows',
                    'approach' => 'direct'
                ],
                [
                    'key' => Str::random(10),
                    'question' => 'What frustrations do you experience with the product?',
                    'description' => 'Identify pain points and areas for improvement',
                    'approach' => 'direct'
                ],
                [
                    'key' => Str::random(10),
                    'question' => 'How would you feel about a chat feature to talk with colleagues?',
                    'description' => 'Validate interest in proposed communication feature',
                    'approach' => 'direct'
                ],
                [
                    'key' => Str::random(10),
                    'question' => 'If you could wave a magic wand and change anything about the product, what would it be?',
                    'description' => 'Uncover aspirational needs and unexpected opportunities',
                    'approach' => 'indirect'
                ],
            ],
        ];
    }
}
