<?php

namespace Database\Seeders;

use App\Models\Interview;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InterviewSeeder extends Seeder
{
    public function run(): void
    {
        // Product/User Interview iDoctus Example
        Interview::factory()->create([
            'name' => 'iDoctus User Experience Interview',
            'language' => 'spanish',
            'agent_name' => 'Mia',
            'interview_type' => 'User Interview',
            'is_public' => true,
            'target_name' => 'iDoctus',
            'target_description' => 'iDoctus es una app para medicos. Información médica precisa, al servicio de sus decisiones clínicas. Consulte medicamentos e interacciones en una app diseñada para apoyar su práctica médica con información científica actualizada.',
            'questions' => [
                [
                    'topic_key' => Str::random(10),
                    'question' => 'How do you currently use our application?',
                    'description' => 'Understand current usage patterns and workflows',
                    'approach' => 'direct'
                ],
                [
                    'topic_key' => Str::random(10),
                    'question' => 'What frustrations do you experience with the product?',
                    'description' => 'Identify pain points and areas for improvement',
                    'approach' => 'direct'
                ],
                [
                    'topic_key' => Str::random(10),
                    'question' => 'How would you feel about a chat feature to talk with colleagues?',
                    'description' => 'Validate interest in proposed communication feature',
                    'approach' => 'indirect'
                ],
                [
                    'topic_key' => Str::random(10),
                    'question' => 'If you could wave a magic wand and change anything about the product, what would it be?',
                    'description' => 'Uncover aspirational needs and unexpected opportunities',
                    'approach' => 'direct'
                ]
            ],
        ]);

        // Recruitment Interview iDoctus Example
        Interview::factory()->create([
            'name' => 'iDoctus Developer Screening Interview',
            'language' => 'english',
            'agent_name' => 'Riley',
            'interview_type' => 'Screening Interview',
            'is_public' => true,
            'target_name' => 'iDoctus',
            'target_description' => 'iDoctus es una app para medicos. Información médica precisa, al servicio de sus decisiones clínicas. Consulte medicamentos e interacciones en una app diseñada para apoyar su práctica médica con información científica actualizada.',
            'questions' => [
                [
                    'topic_key' => Str::random(10),
                    'question' => 'What are your career goals?',
                    'description' => 'Understand the candidate\'s aspirations and how they align with the company\'s vision.',
                    'approach' => 'direct'
                ],
                [
                    'topic_key' => Str::random(10),
                    'question' => 'Have you experience in testing software?',
                    'description' => 'For us it\'s important to know if you have experience in testing software.',
                    'approach' => 'direct',
                ],
                [
                    'topic_key' => Str::random(10),
                    'question' => 'What software design patterns or principles do you regularly apply in your work?',
                    'description' => 'Evaluate their knowledge of SOLID principles, design patterns, and development best practices.',
                    'approach' => 'direct'
                ],
                [
                    'topic_key' => Str::random(10),
                    'question' => 'If you could change one thing about your last job, what would it be?',
                    'description' => 'Identify areas of dissatisfaction and potential red flags.',
                    'approach' => 'indirect'
                ]
            ],
        ]);

        // Customer Feedback Interview Amalfi Restaurant Example
        Interview::factory()->create([
            'name' => 'Amalfi Restaurant Customer Feedback',
            'language' => 'spanish',
            'agent_name' => 'Lucia',
            'interview_type' => 'Customer Feedback',
            'is_public' => true,
            'target_name' => 'Amalfi',
            'target_description' => 'Amalfi is an authentic Italian restaurant specializing in pasta fresca and traditional pizza. The restaurant offers a warm ambiance with a focus on fresh, locally-sourced ingredients and classic Italian recipes from the Amalfi Coast region.',
            'questions' => [
                [
                    'topic_key' => Str::random(10),
                    'question' => 'How would you rate your overall dining experience at Amalfi today?',
                    'description' => 'Evaluate general customer satisfaction with their visit',
                    'approach' => 'direct'
                ],
                [
                    'topic_key' => Str::random(10),
                    'question' => 'What did you think about the quality and authenticity of our pasta dishes?',
                    'description' => 'Assess perception of core menu offerings and their authenticity',
                    'approach' => 'direct'
                ],
                [
                    'topic_key' => Str::random(10),
                    'question' => 'How was the service provided by our staff during your visit?',
                    'description' => 'Gather feedback on staff performance and service quality',
                    'approach' => 'direct'
                ],
                [
                    'topic_key' => Str::random(10),
                    'question' => 'What would you like to see added to our menu in the future?',
                    'description' => 'Identify opportunities for menu expansion and improvement',
                    'approach' => 'direct'
                ],
                [
                    'topic_key' => Str::random(10),
                    'question' => 'How likely are you to recommend Amalfi to friends or family?',
                    'description' => 'Measure likelihood of word-of-mouth promotion',
                    'approach' => 'direct'
                ]
            ],
        ]);

        // Quick Test Interview - Single Question
        Interview::factory()->create([
            'name' => 'EcoTech Product Validation',
            'language' => 'english',
            'agent_name' => 'Alex',
            'interview_type' => 'Market Research',
            'is_public' => true,
            'target_name' => 'SolarPod',
            'target_description' => 'SolarPod is a portable solar charging device that uses advanced photovoltaic technology to efficiently charge smartphones and small electronics. The product is designed for outdoor enthusiasts, travelers, and environmentally conscious consumers looking for sustainable charging solutions.',
            'questions' => [
                [
                    'topic_key' => Str::random(10),
                    'question' => 'On a scale of 1-10, how likely would you be to purchase a portable solar charger for $49.99 that can fully charge your smartphone in 2 hours of sunlight?',
                    'description' => 'Gauge price sensitivity and overall product interest for the core value proposition',
                    'approach' => 'direct'
                ]
            ],
        ]);

        // Comprehensive Job Candidate Interview - 10 Questions
        Interview::factory()->create([
            'name' => 'NexGen Tech Full-Stack Developer Interview',
            'language' => 'english',
            'agent_name' => 'Morgan',
            'interview_type' => 'Job Interview',
            'is_public' => true,
            'target_name' => 'NexGen Technologies',
            'target_description' => 'NexGen Technologies is a rapidly growing software development company specializing in AI-powered SaaS solutions for healthcare, finance, and education sectors. The company employs a modern tech stack including React, Node.js, Python, and AWS, and values innovation, collaboration, and work-life balance.',
            'questions' => [
                [
                    'topic_key' => Str::random(10),
                    'question' => 'Could you walk me through your professional background and experience that\'s relevant to this full-stack developer role?',
                    'description' => 'Assess candidate\'s overall experience and background fit for the position',
                    'approach' => 'direct'
                ],
                [
                    'topic_key' => Str::random(10),
                    'question' => 'What aspects of modern web development are you most passionate about?',
                    'description' => 'Evaluate candidate\'s enthusiasm and areas of technical interest',
                    'approach' => 'direct'
                ],
                [
                    'topic_key' => Str::random(10),
                    'question' => 'Describe a technically challenging project you worked on. What problems did you encounter and how did you solve them?',
                    'description' => 'Assess problem-solving abilities and technical resilience',
                    'approach' => 'direct'
                ],
                [
                    'topic_key' => Str::random(10),
                    'question' => 'How do you approach optimizing application performance, and what metrics do you typically focus on?',
                    'description' => 'Evaluate understanding of performance optimization techniques',
                    'approach' => 'direct'
                ],
                [
                    'topic_key' => Str::random(10),
                    'question' => 'How do you stay current with rapidly evolving web technologies and frameworks?',
                    'description' => 'Assess candidate\'s commitment to continuous learning',
                    'approach' => 'direct'
                ],
                [
                    'topic_key' => Str::random(10),
                    'question' => 'Tell me about your experience working with agile development methodologies.',
                    'description' => 'Evaluate familiarity with agile processes and team collaboration',
                    'approach' => 'direct'
                ],
                [
                    'topic_key' => Str::random(10),
                    'question' => 'If you noticed a team member\'s code introducing a potential security vulnerability, how would you address it?',
                    'description' => 'Assess communication skills and security awareness',
                    'approach' => 'indirect'
                ],
                [
                    'topic_key' => Str::random(10),
                    'question' => 'How would you feel about being on call occasionally for production support?',
                    'description' => 'Gauge willingness to handle responsibilities beyond regular development',
                    'approach' => 'indirect'
                ],
                [
                    'topic_key' => Str::random(10),
                    'question' => 'What work environment helps you perform at your best?',
                    'description' => 'Assess cultural fit and work style preferences',
                    'approach' => 'direct'
                ],
                [
                    'topic_key' => Str::random(10),
                    'question' => 'Do you have any questions about NexGen Technologies or the role we haven\'t covered yet?',
                    'description' => 'Evaluate candidate\'s preparation and genuine interest in the position',
                    'approach' => 'direct'
                ]
            ],
        ]);
    }
}
