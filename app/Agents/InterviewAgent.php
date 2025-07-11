<?php

namespace App\Agents;

use App\Enums\InterviewSessionStatus;
use App\Models\Interview;
use App\Models\InterviewSession;
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
    public function chat(string $sessionId, string $message, Interview $interview, bool $isStaleSession = false): array
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
        $isFirstMessage = empty($messages);

        // Check if this is the first interaction and a welcome message is defined
        $hasCustomWelcomeMessage = !empty($interview->welcome_message);
        $hasCustomGoodbyeMessage = !empty($interview->goodbye_message);

        // Calculate turn count based on message history
        $turnCount = $this->calculateTurnCount($messages);

        // Filter out disabled topics
        $enabledTopics = array_values(array_filter($interview->topics, fn($topic) => $topic['enabled'] === true));

        $maxTurns = count($enabledTopics) * 5;

        // Check if we've reached the turn limit
        $reachedTurnLimit = $turnCount >= $maxTurns;

        if ($isFirstMessage && $hasCustomWelcomeMessage) {
            // For the first interaction in a new session, use the welcome message
            $messages[] = new AssistantMessage($interview->welcome_message);
            $this->saveMessages($sessionId, $messages, $interview, 0, 0, 0);
        }

        // Only add the user message to the history if it's not empty (initialization case)
        if (strlen(trim($message)) > 0) {
            $messages[] = new UserMessage($message);
        }

        $systemPrompt = view('agents.interview.system-prompt', [
            'language' => $interview->language,
            'agentName' => $interview->agent_name,
            'interviewType' => $interview->interview_type,
            'targetName' => $interview->target_name,
            'targetDescription' => $interview->target_description,
            'targetAdditionalContext' => $interview->target_additional_context,
            'topics' => $enabledTopics,
            'hasCustomWelcomeMessage' => $hasCustomWelcomeMessage,
            'hasCustomGoodbyeMessage' => $hasCustomGoodbyeMessage,
            'turnsExhausted' => $reachedTurnLimit,
            'isStaleSession' => $isStaleSession,
        ]);

        $providerName = config('agent.provider');
        $modelName = config('agent.model');

        $response = Prism::structured()
            ->using($providerName, $modelName)
            ->withSchema($schema)
            ->withSystemPrompt($systemPrompt)
            ->withMessages($messages)
            ->withClientRetry(3)
            ->asStructured();

        $output = $response->structured ?? [];

        // Get token usage from the response
        $inputTokens = $response->usage->promptTokens ?? 0;
        $outputTokens = $response->usage->completionTokens ?? 0;

        // Calculate cost based on token usage and configuration
        $cost = $this->calculateCost($inputTokens, $outputTokens);

        if ($isStaleSession) {
            $this->saveMessages($sessionId, $messages, $interview, $inputTokens, $outputTokens, $cost);

            return [
                'summary' => $output['result']['summary'] ?? null,
                'topics' => $output['result']['topics'] ?? [],
            ];
        }

        // For normal sessions, process the output as before
        $finished = !empty($output['finished']) && $output['finished'] === true;

        if ($finished && $hasCustomGoodbyeMessage) {
            $output['messages'] = [$interview->goodbye_message];
        }

        // Create a filtered version of the messages
        $filteredMessages = [];
        if (!empty($output['messages'])) {
            // Filter out empty messages
            $filteredMessages = array_values(
                array_filter($output['messages'], fn($msg) => !empty(trim($msg)))
            );

            // Limit to maximum of 2 messages as per system prompt
            $filteredMessages = array_slice($filteredMessages, 0, 2);

            // Store each non-empty message in the conversation history
            foreach ($filteredMessages as $messageContent) {
                $messages[] = new AssistantMessage($messageContent);
            }
        }

        $output['messages'] = [
            ...$isFirstMessage && $hasCustomWelcomeMessage ? [$interview->welcome_message] : [],
            ...$filteredMessages,
        ];

        // Save messages along with token usage and cost
        $this->saveMessages($sessionId, $messages, $interview, $inputTokens, $outputTokens, $cost);

        // Check if the interview is finished and update the session record
        if ($finished) {
            $this->finalizeSession(
                $sessionId,
                $output['result']['summary'] ?? null,
                $output['result']['topics'] ?? null,
                $interview
            );
        }

        return $output;
    }

    /**
     * Calculate the number of turns in the conversation based on message history
     * A turn consists of a user message followed by one or more assistant messages
     *
     * @param array $messages Array of messages in the conversation
     * @return int The number of turns
     */
    private function calculateTurnCount(array $messages): int
    {
        return
            count(array_filter($messages, fn($message) => $message instanceof UserMessage))
            + 1 // the current turn
            ;
    }

    /**
     * Calculate the cost of token usage based on the provider and model
     *
     * @param int $inputTokens Number of input tokens
     * @param int $outputTokens Number of output tokens
     * @return float The calculated cost
     */
    private function calculateCost(int $inputTokens, int $outputTokens): float
    {
        $inputPrice = config('agent.pricing.input');
        $outputPrice = config('agent.pricing.output');

        // Calculate cost (convert from per million tokens to per token)
        $inputCost = ($inputTokens / 1_000_000) * $inputPrice;
        $outputCost = ($outputTokens / 1_000_000) * $outputPrice;

        return $inputCost + $outputCost;
    }

    private function loadPreviousMessages(string $sessionId): array
    {
        $session = InterviewSession::where('id', $sessionId)->first();
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

    private function saveMessages(
        string $sessionId,
        array $messages,
        Interview $interview,
        int $inputTokens = 0,
        int $outputTokens = 0,
        float $cost = 0
    ): void
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

        // Get current session to accumulate token counts
        $session = InterviewSession::where('id', $sessionId)->first();

        $currentInputTokens = ($session ? $session->input_tokens : 0) + $inputTokens;
        $currentOutputTokens = ($session ? $session->output_tokens : 0) + $outputTokens;
        $currentCost = ($session ? $session->cost : 0) + $cost;

        InterviewSession::updateOrCreate(
            ['id' => $sessionId],
            [
                'interview_id' => $interview->id,
                'messages' => $cachedMessages,
                'input_tokens' => $currentInputTokens,
                'output_tokens' => $currentOutputTokens,
                'cost' => $currentCost,
            ]
        );
    }

    private function finalizeSession(string $sessionId, ?string $summary, ?array $topics, Interview $interview): void
    {
        InterviewSession::where('id', $sessionId)
            ->where('interview_id', $interview->id)
            ->update([
                'summary' => $summary,
                'topics' => $topics,
                'status' => InterviewSessionStatus::completed,
            ]);
    }
}
