<?php

namespace App\Agents;

use App\Models\Interview;
use App\Models\InterviewSession;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\BooleanSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;
use function view;

class InterviewAgent
{
    public function chat(string $sessionId, string $message, Interview $interview): mixed
    {
        $topicSchema = new ObjectSchema(
            name: 'topic',
            description: 'Information collected about a specific topic',
            properties: [
                new StringSchema(
                    name: 'key',
                    description: 'The unique identifier for the topic (a string of 10 characters)'
                ),
                new ArraySchema(
                    name: 'messages',
                    description: 'Array of messages related to this topic',
                    items: new StringSchema(
                        name: 'message',
                        description: 'A message or piece of information related to the topic'
                    )
                )
            ],
            requiredFields: ['key', 'messages']
        );

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
                new BooleanSchema(
                    name: 'finished',
                    description: 'Boolean flag indicating if the interview is finished',
                ),
                new ObjectSchema(
                    'result',
                    'The interview results data',
                    properties: [
                        new StringSchema(
                            name: 'summary',
                            description: 'A concise summary of the interview',
                        ),
                        new ArraySchema(
                            name: 'topics',
                            description: 'Array of topic entries containing information collected during the interview',
                            items: $topicSchema
                        )
                    ],
                ),
            ],
            requiredFields: ['messages', 'finished']
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
            'topics' => $interview->topics,
        ]);

        $response = Prism::structured()
            ->using(Provider::OpenAI, 'o4-mini')
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

        // Check if the interview is finished and update the session record
        if (!empty($output['finished']) && $output['finished'] === true) {
            $this->finalizeSession(
                $sessionId, 
                $output['result']['summary'] ?? null, 
                $output['result']['topics'] ?? null
            );
        }

        // Create a new response object to return if needed
        // or just return the original response - the filtered messages have been saved to history
        return $output;
    }

    private function loadPreviousMessages(string $sessionId): array
    {
        $session = InterviewSession::where('session_id', $sessionId)->first();
        $cachedMessages = $session ? $session->messages : [];
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

        // Extract interview_id from sessionId (format: "interview_{interview_id}_{uuid}")
        preg_match('/interview_(\d+)_/', $sessionId, $matches);
        $interviewId = $matches[1] ?? null;

        if ($interviewId) {
            InterviewSession::updateOrCreate(
                ['session_id' => $sessionId],
                [
                    'interview_id' => $interviewId,
                    'messages' => $cachedMessages,
                ]
            );
        }
    }

    private function finalizeSession(string $sessionId, ?string $summary, ?array $topics): void
    {
        InterviewSession::where('session_id', $sessionId)->update([
            'summary' => $summary,
            'topics' => $topics,
            'finished' => true,
        ]);
    }
}
