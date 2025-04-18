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
use function view;

class InterviewAgent
{
    public function chat(string $sessionId, string $message, Interview $interview): mixed
    {
        $schema = new ObjectSchema(
            name: 'agent_response',
            description: 'A structured response from the agent',
            properties: [
                new ArraySchema(
                    name: 'messages',
                    description: 'An array of messages from the agent to be displayed sequentially',
                    items: new StringSchema(
                        name: 'message',
                        description: 'A single message from the agent',
                    )
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
            requiredFields: ['messages']
        );

        $messages = $this->loadPreviousMessages($sessionId);

        // Only add the user message to the history if it's not empty (initialization case)
        if (!empty(trim($message))) {
            $messages[] = new UserMessage($message);
        }

        // Get system prompt with language instruction and agent name
        $systemPrompt = view('agents.interview.system-prompt', [
            'language' => $interview->language,
            'agentName' => $interview->agent_name,
            'interviewType' => $interview->interview_type,
            'targetName' => $interview->target_name,
            'targetDescription' => $interview->target_description,
            'questions' => $interview->questions,
        ]);

        $response = Prism::structured()
            ->using(Provider::OpenAI, 'o3-mini')
            // ->using(Provider::DeepSeek, 'deepseek-chat')
            ->withSchema($schema)
            ->withSystemPrompt($systemPrompt)
            ->withMessages($messages)
            ->withClientRetry(3)
            ->asStructured();

        $output = $response->structured ?? [];

        // Create a filtered version of the messages
        $filteredMessages = [];
        if (!empty($output['messages'])) {
            // Filter out empty messages
            $filteredMessages = array_values(
                array_filter($output['messages'], fn($msg) => !empty(trim($msg)))
            );

            // Store each non-empty message in the conversation history
            foreach ($filteredMessages as $messageContent) {
                $messages[] = new AssistantMessage($messageContent);
            }
        }

        $this->saveMessages($sessionId, $messages);

        // Create a new response object to return if needed
        // or just return the original response - the filtered messages have been saved to history
        return $output;
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
