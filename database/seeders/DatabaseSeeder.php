<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Interview;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Product/User Interview iDoctus Example
        Interview::factory()->create([
            'language' => 'spanish',
            'agent_name' => 'Mia',
            'interview_type' => 'User Interview',
            'is_public' => true,
            'target_name' => 'iDoctus',
            'target_description' => 'iDoctus es una app para medicos. Información médica precisa, al servicio de sus decisiones clínicas. Consulte medicamentos e interacciones en una app diseñada para apoyar su práctica médica con información científica actualizada.',
            'questions' => [
                [
                    'topic_id' => 'topic_1',
                    'question' => 'How do you currently use our application?',
                    'description' => 'Understand current usage patterns and workflows',
                    'approach' => 'direct'
                ],
                [
                    'topic_id' => 'topic_2',
                    'question' => 'What frustrations do you experience with the product?',
                    'description' => 'Identify pain points and areas for improvement',
                    'approach' => 'direct'
                ],
                [
                    'topic_id' => 'topic_3',
                    'question' => 'How would you feel about a chat feature to talk with colleagues?',
                    'description' => 'Validate interest in proposed communication feature',
                    'approach' => 'indirect'
                ],
                [
                    'topic_id' => 'topic_4',
                    'question' => 'If you could wave a magic wand and change anything about the product, what would it be?',
                    'description' => 'Uncover aspirational needs and unexpected opportunities',
                    'approach' => 'direct'
                ]
            ],
        ]);

        // Recruitment Interview iDoctus Example
        Interview::factory()->create([
            'language' => 'english',
            'agent_name' => 'Riley',
            'interview_type' => 'Screening Interview',
            'is_public' => true,
            'target_name' => 'iDoctus',
            'target_description' => 'iDoctus es una app para medicos. Información médica precisa, al servicio de sus decisiones clínicas. Consulte medicamentos e interacciones en una app diseñada para apoyar su práctica médica con información científica actualizada.',
            'questions' => [
                [
                    'topic_id' => 'topic_1',
                    'question' => 'What are your career goals?',
                    'description' => 'Understand the candidate\'s aspirations and how they align with the company\'s vision.',
                    'approach' => 'direct'
                ],
                [
                    'topic_id' => 'topic_2',
                    'question' => 'Have you experience in testing software?',
                    'description' => 'For us it\'s important to know if you have experience in testing software.',
                    'approach' => 'direct',
                ],
                [
                    'topic_id' => 'topic_3',
                    'question' => 'What software design patterns or principles do you regularly apply in your work?',
                    'description' => 'Evaluate their knowledge of SOLID principles, design patterns, and development best practices.',
                    'approach' => 'direct'
                ],
                [
                    'topic_id' => 'topic_4',
                    'question' => 'If you could change one thing about your last job, what would it be?',
                    'description' => 'Identify areas of dissatisfaction and potential red flags.',
                    'approach' => 'indirect'
                ]
            ],
        ]);
        
        // Customer Feedback Interview Amalfi Restaurant Example
        Interview::factory()->create([
            'language' => 'spanish',
            'agent_name' => 'Lucia',
            'interview_type' => 'Customer Feedback',
            'is_public' => true,
            'target_name' => 'Amalfi',
            'target_description' => 'Amalfi is an authentic Italian restaurant specializing in pasta fresca and traditional pizza. The restaurant offers a warm ambiance with a focus on fresh, locally-sourced ingredients and classic Italian recipes from the Amalfi Coast region.',
            'questions' => [
                [
                    'topic_id' => 'topic_1',
                    'question' => 'How would you rate your overall dining experience at Amalfi today?',
                    'description' => 'Evaluate general customer satisfaction with their visit',
                    'approach' => 'direct'
                ],
                [
                    'topic_id' => 'topic_2',
                    'question' => 'What did you think about the quality and authenticity of our pasta dishes?',
                    'description' => 'Assess perception of core menu offerings and their authenticity',
                    'approach' => 'direct'
                ],
                [
                    'topic_id' => 'topic_3',
                    'question' => 'How was the service provided by our staff during your visit?',
                    'description' => 'Gather feedback on staff performance and service quality',
                    'approach' => 'direct'
                ],
                [
                    'topic_id' => 'topic_4',
                    'question' => 'What would you like to see added to our menu in the future?',
                    'description' => 'Identify opportunities for menu expansion and improvement',
                    'approach' => 'direct'
                ],
                [
                    'topic_id' => 'topic_5',
                    'question' => 'How likely are you to recommend Amalfi to friends or family?',
                    'description' => 'Measure likelihood of word-of-mouth promotion',
                    'approach' => 'direct'
                ]
            ],
        ]);
    }
}
