<?php

namespace Database\Factories;

use App\Enums\InterviewSessionStatus;
use App\Models\Interview;
use App\Models\InterviewSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InterviewSession>
 */
class InterviewSessionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = InterviewSession::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(), // Generate a UUID v7 for the primary key
            'interview_id' => Interview::factory(), // Associate with an Interview
            'messages' => [], // Default to an empty array
            'summary' => null, // Default to null
            'topics' => [], // Default to an empty array
            'status' => InterviewSessionStatus::IN_PROGRESS, // Default to in progress
        ];
    }
}
