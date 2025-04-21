<?php

namespace App\Http\Controllers;

use App\Models\Interview;
use App\Models\InterviewSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class InterviewController extends Controller
{
    public function __invoke(Request $request, Interview $interview): Response
    {
        if (!$interview->is_public && !$request->hasValidSignature()) {
            abort(SymfonyResponse::HTTP_FORBIDDEN, 'Invalid or expired interview link');
        }

        // Generate a session key that's tied to the specific interview
        $interviewSessionKey = "interview_{$interview->id}_session_id";

        // Generate a new session ID or retrieve existing one from session
        $sessionId = $request->session()->get($interviewSessionKey);

        if (!$sessionId) {
            $sessionId = "interview_{$interview->id}_" . Str::uuid()->toString();
            $request->session()->put($interviewSessionKey, $sessionId);
        }

        // Load messages from database
        $session = InterviewSession::firstOrCreate(
            ['session_id' => $sessionId],
            [
                'interview_id' => $interview->id, 
                'messages' => [],
                'metadata' => [
                    'query_parameters' => $request->query()
                ]
            ]
        );
        
        $messages = [];
        foreach ($session->messages as $index => $message) {
            $messages[] = [
                'id' => "{$sessionId}_{$index}",
                'sender' => $message['type'] === 'assistant' ? 'ai' : $message['type'],
                'text' => $message['content'],
                'status' => 'sent'
            ];
        }

        // Send is_finished directly instead of wrapping it in a session object
        return Inertia::render('Chat', [
            'interview' => [
                'id' => $interview->id,
                'name' => $interview->name,
                'agent_name' => $interview->agent_name,
                'language' => $interview->language,
                'company_name' => $interview->company_name,
                'product_name' => $interview->product_name,
                'product_description' => $interview->product_description,
                'is_public' => $interview->is_public,
            ],
            'sessionId' => $sessionId,
            'messages' => $messages,
            'is_finished' => (bool) $session->finished
        ]);
    }

    public static function generateSignedUrl(Interview $interview): string
    {
        if ($interview->is_public) {
            return route('interview', $interview);
        }

        return URL::signedRoute('interview', ['interview' => $interview]);
    }
}
