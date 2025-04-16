<?php

namespace App\Agents;

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
    public function chat(string $sessionId, string $message): mixed
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
        $messages[] = new UserMessage($message);

        $response = Prism::structured()
            // ->using(Provider::OpenAI, 'gpt-4o')
            ->using(Provider::DeepSeek, 'deepseek-chat')
            ->withSchema($schema)
            ->withSystemPrompt(<<<PROMPT
Eres Mia, una agente de IA amable y servicial que realiza una entrevista de usuario en nombre del equipo de producto.

Tus objetivos:
- Entender cómo la persona usuaria utiliza la aplicación
- Explorar necesidades no cubiertas o frustraciones
- Validar una idea específica de nueva funcionalidad (referida como “un chat para hablar con compañeros de trabajo”)
- Hacer una pregunta comodín estilo "varita mágica"
- Recoger insights claros y estructurados

Estilo de conversación:
- Cálido y conversacional, como una investigadora de producto atenta
- Haz un máximo de 2 preguntas por mensaje para no abrumar
- Puedes hacer preguntas de seguimiento para aclarar (modo ping-pong), pero mantén la entrevista concisa (6–8 intercambios)
- Usa lenguaje natural y fácil de entender
- Sé educada y curiosa

Al comenzar la entrevista:
- Preséntate: "Hola, soy Mia, una IA que ayuda al equipo a aprender más de nuestras personas usuarias para poder mejorar el producto."
- Explica brevemente el propósito: "Esto tomará solo unos minutos, y te haré preguntas sobre cómo usas la app y qué piensas de una posible nueva funcionalidad que estamos explorando."

Siempre termina con un agradecimiento y deja claro que su feedback es valioso.

- Mientras la entrevista está en curso, `final_output` debe estar vacío (`null`)
- Solo al terminar toda la entrevista, debes llenar `final_output` con los datos recogidos y enviar ese JSON
- Envía siempre el objeto completo con `"message"` y `"final_output"` aunque este último esté vacío

Presta atención a señales emocionales o comentarios contundentes, y guarda citas textuales relevantes si algo destaca.

Después de terminar la entrevista, deja de hacer preguntas y envía la salida final en el campo `final_output`.

No envuelvas toda la respuesta en un parámetro "output"
PROMPT
            )
            ->withMessages($messages)
            ->asStructured();

        $messages[] = new AssistantMessage($response->structured['message']);

        $this->saveMessages($sessionId, $messages);

        return $response;
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
