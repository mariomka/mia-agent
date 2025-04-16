<?php

namespace Database\Factories;

use App\Models\Interview;
use Illuminate\Database\Eloquent\Factories\Factory;

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
        
        return [
            'name' => fake()->sentence(3),
            'agent_name' => fake()->randomElement($agentNames),
            'language' => fake()->randomElement($languages),
            'is_public' => fake()->boolean(70), // 70% chance of being public
        ];
    }
}
