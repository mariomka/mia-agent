<?php

namespace App\Agents;

use App\Models\Interview;
use Illuminate\Support\Facades\Cache;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;

class InterviewAgent
{
    public function chat(string $sessionId, string $message, Interview $interview): mixed
    {
        $schema = new ObjectSchema(
            name: 'agent_response',
            description: 'A structured response from the agent',
            properties: [
                new StringSchema(
                    'message',
                    'The message from the agent',
                ),
                new ObjectSchema(
                    'final_output',
                    'The final output of the agent',
                    properties: [
                        new ArraySchema(
                            name: 'app_usage',
                            description: 'The user usage of the app',
                            items: new StringSchema(
                                name: 'usage',
                                description: 'A usage entry',
                            )
                        ),
                        new ArraySchema(
                            name: 'app_needs',
                            description: 'The user needs and frustrations',
                            items: new StringSchema(
                                name: 'need',
                                description: 'A need entry',
                            )
                        ),
                        new ArraySchema(
                            name: 'feature_validation',
                            description: 'The validation of the new feature',
                            items: new StringSchema(
                                name: 'validation',
                                description: 'A validation entry',
                            )
                        ),
                        new ArraySchema(
                            name: 'magic_wand_request',
                            description: 'The magic wand request',
                            items: new StringSchema(
                                name: 'request',
                                description: 'A request entry',
                            )
                        ),
                    ],
                ),
            ],
            requiredFields: ['message']
        );

        $messages = $this->loadPreviousMessages($sessionId);
        
        // Only add the user message to the history if it's not empty (initialization case)
        if (!empty(trim($message))) {
            $messages[] = new UserMessage($message);
        }

        // Get system prompt with language instruction and agent name
        $systemPrompt = $this->getSystemPrompt(
            language: $interview->language,
            agentName: $interview->agent_name,
            companyName: $interview->company_name,
            productName: $interview->product_name,
            productDescription: $interview->product_description,
            questions: $interview->questions
        );

        $response = Prism::structured()
            // ->using(Provider::OpenAI, 'gpt-4o')
            ->using(Provider::DeepSeek, 'deepseek-chat')
            ->withSchema($schema)
            ->withSystemPrompt($systemPrompt)
            ->withMessages($messages)
            ->asStructured();

        // Only save the assistant message to history
        $messages[] = new AssistantMessage($response->structured['message']);

        $this->saveMessages($sessionId, $messages);

        return $response;
    }

    private function getSystemPrompt(
        string $language, 
        string $agentName, 
        ?string $companyName = null, 
        ?string $productName = null, 
        ?string $productDescription = null,
        ?array $questions = null
    ): string
    {
        $companyContext = $companyName ? "You are conducting this interview on behalf of {$companyName}." : "";
        $productContext = "";
        
        if ($productName) {
            $productContext .= "The product you're discussing is called {$productName}.";
            
            if ($productDescription) {
                $productContext .= " {$productDescription}";
            }
        }
        
        $questionsContext = "";
        if ($questions && is_array($questions)) {
            $questionsContext = "You need to gather information about these specific topics:\n";
            
            foreach ($questions as $index => $question) {
                $questionText = $question['question'] ?? 'N/A';
                $description = $question['description'] ?? 'N/A';
                $approach = $question['approach'] ?? 'direct';
                
                $questionsContext .= "- Topic " . ($index + 1) . ": {$description}\n";
                
                if ($approach === 'direct') {
                    $questionsContext .= "  You can ask directly: \"{$questionText}\"\n";
                } else {
                    $questionsContext .= "  Ask indirectly about: \"{$questionText}\"\n";
                    $questionsContext .= "  Instead of asking this directly, find creative ways to get this information through conversation.\n";
                }
            }
        }
        
        return <<<PROMPT
You are {$agentName}, a friendly and helpful AI agent conducting a user interview on behalf of the product team.

IMPORTANT: You must communicate with the user in {$language}. All your responses should be in {$language}.

{$companyContext}
{$productContext}

Your goals:
- Understand how the user uses the application
- Explore unmet needs or frustrations
- Validate a specific new feature idea (referred to as "a chat to talk with colleagues")
- Ask a magic wand style question
- Collect clear and structured insights

{$questionsContext}

Conversation style:
- Warm and conversational, like an attentive product researcher
- Ask a maximum of 2 questions per message to avoid overwhelming
- You can ask follow-up questions to clarify (ping-pong mode), but keep the interview concise (6-8 exchanges)
- Use natural and easy to understand language
- Be polite and curious

When starting the interview:
- Introduce yourself in {$language}: Example in English: "Hi, I'm {$agentName}, an AI that helps the team learn more about our users so we can improve the product." Or in Spanish: "Hola, soy {$agentName}, una IA que ayuda al equipo a aprender más de nuestras personas usuarias para poder mejorar el producto."
- Briefly explain the purpose in {$language}

Always end with a thank you and make it clear that their feedback is valuable.

- While the interview is in progress, `final_output` should be empty (`null`)
- Only at the end of the entire interview, you should fill in `final_output` with the collected data and send that JSON
- Always send the complete object with `"message"` and `"final_output"` even if the latter is empty

Pay attention to emotional signals or strong comments, and save relevant verbatim quotes if something stands out.

After finishing the interview, stop asking questions and send the final output in the `final_output` field.

Do not wrap the entire response in an "output" parameter
PROMPT;
    }

    private function loadPreviousMessages(string $sessionId): array
    {
        $cachedMessages = Cache::get("chat_{$sessionId}", []);
        $messages = [];

        foreach ($cachedMessages as $message) {
            $messages[] = match ($message['type']) {
                'user' => new UserMessage($message['content']),
                'assistant' => new AssistantMessage($message['content']),
            };
        }

        return $messages;
    }

    private function saveMessages(string $sessionId, array $messages): void
    {
        $cachedMessages = [];

        foreach ($messages as $message) {
            $cachedMessages[] = [
                'type' => match ($message::class) {
                    UserMessage::class => 'user',
                    AssistantMessage::class => 'assistant',
                },
                'content' => $message->content,
            ];
        }

        Cache::put("chat_{$sessionId}", $cachedMessages, now()->addMinutes(30));
    }
}
