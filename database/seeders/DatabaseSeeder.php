<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \App\Models\Interview::factory()->create([
            'language' => 'spanish',
            'agent_name' => 'Mia',
            'is_public' => true,
            'company_name' => 'iDoctus',
            'product_name' => 'iDoctus',
            'product_description' => 'iDoctus es una app para medicos. Información médica precisa, al servicio de sus decisiones clínicas. Consulte medicamentos e interacciones en una app diseñada para apoyar su práctica médica con información científica actualizada',
            'questions' => [
                [
                    'question' => 'How do you currently use our application?',
                    'description' => 'Understand current usage patterns and workflows',
                    'approach' => 'direct'
                ],
                [
                    'question' => 'What frustrations do you experience with the product?',
                    'description' => 'Identify pain points and areas for improvement',
                    'approach' => 'direct'
                ],
                [
                    'question' => 'How would you feel about a chat feature to talk with colleagues?',
                    'description' => 'Validate interest in proposed communication feature',
                    'approach' => 'indirect'
                ],
                [
                    'question' => 'If you could wave a magic wand and change anything about the product, what would it be?',
                    'description' => 'Uncover aspirational needs and unexpected opportunities',
                    'approach' => 'direct'
                ]
            ],
        ]);
    }
}
