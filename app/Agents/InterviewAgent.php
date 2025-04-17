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
        $systemPrompt = $this->getSystemPrompt(
            language: $interview->language,
            agentName: $interview->agent_name,
            targetName: $interview->target_name,
            targetDescription: $interview->target_description,
            questions: $interview->questions,
            interviewType: $interview->interview_type
        );

        $response = Prism::structured()
            ->using(Provider::OpenAI, 'gpt-4o')
            // ->using(Provider::DeepSeek, 'deepseek-chat')
            ->withSchema($schema)
            ->withSystemPrompt($systemPrompt)
            ->withMessages($messages)
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

    private function getSystemPrompt(
        string $language,
        string $agentName,
        ?string $targetName = null,
        ?string $targetDescription = null,
        ?array $questions = null,
        ?string $interviewType = null
    ): string
    {
        $targetContext = "";

        // Determine the interview purpose based on type
        $interviewPurpose = match($interviewType) {
            'Screening Interview' => 'to evaluate candidates',
            'User Interview' => 'on behalf of the product team',
            'Customer Feedback' => 'to gather valuable feedback',
            'Market Research' => 'to understand market trends',
            default => 'with our users'
        };

        // Add context based on interview type
        $typeContext = "";
        if ($interviewType) {
            $typeContext = "This is a {$interviewType}.";

            if ($interviewType === 'User Interview') {
                $typeContext .= " Focus on understanding user needs, pain points, and workflows to improve the product.";
            } elseif ($interviewType === 'Screening Interview') {
                $typeContext .= " Focus on assessing the candidate's skills, experience, and fit for the role.";
            } elseif ($interviewType === 'Customer Feedback') {
                $typeContext .= " Focus on gathering detailed feedback about existing features and potential improvements.";
            } elseif ($interviewType === 'Market Research') {
                $typeContext .= " Focus on understanding market trends, competitor analysis, and user preferences.";
            }
        }

        if ($targetName) {
            $targetContext .= "The target you're discussing is called {$targetName}.";

            if ($targetDescription) {
                $targetContext .= " {$targetDescription}";
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
You are {$agentName}, a friendly and helpful AI agent conducting a {$interviewType} {$interviewPurpose}.

IMPORTANT: You must communicate with the user in {$language}. All your responses should be in {$language}.

# Context
{$targetContext}
{$typeContext}

# Conversation style
- Warm and conversational
- Use natural and easy to understand language
- Be polite and curious

# Interview Guidelines
- **You MUST cover ALL the topics/questions listed bellow**
- For each topic, use between 1-5 question/answer exchanges to gather sufficient information
- Your primary role is to ASK questions, not to provide answers or solutions
- Ask only ONE question at a time, unless questions are directly related to the same specific topic
- Avoid answering the user's questions - politely redirect to your interview questions
- Focus exclusively on gathering information related to the specified topics
- Only discuss what's mentioned in the current conversation

# Questions
{$questionsContext}

# Message Structure Guidelines
- Return your responses in the `messages` array
- You can split your messages into multiple separate ones for better readability
- Each message in the array will be displayed to the user sequentially
- **Limit your response to maximum 2 messages per turn**
- **Each message must be 300 characters or less**
- Keep each message focused on a single thought or question
- For introductions or complex topics, prioritize key information within the character limits

# When starting the interview:
- Introduce yourself and briefly explain the purpose of this interview

# When the interview is in progress:
- While the interview is in progress, `final_output` should be empty (`null`)

# When the interview is finished:
- When you've covered all required topics, end the interview without asking for additional feedback, you can say to contact with us if they want to add more information
- Fill in `final_output` with the collected data and send that JSON

Always send the complete object with `"messages"` and `"final_output"` even if the latter is empty

Pay attention to emotional signals or strong comments, and save relevant verbatim quotes if something stands out.
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
