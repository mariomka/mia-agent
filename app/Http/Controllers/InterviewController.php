<?php

namespace App\Http\Controllers;

use App\Models\Interview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

        // Generate a new session ID or retrieve existing one from session
        $sessionId = $request->session()->get('interview_session_id');
        
        if (!$sessionId) {
            $sessionId = Str::uuid()->toString();
            $request->session()->put('interview_session_id', $sessionId);
        }
        
        // Load messages from cache
        $cachedMessages = Cache::get("chat_{$sessionId}", []);
        $messages = [];
        
        foreach ($cachedMessages as $index => $message) {
            $messages[] = [
                'id' => "{$sessionId}_{$index}",
                'sender' => $message['type'] === 'assistant' ? 'ai' : $message['type'],
                'text' => $message['content'],
                'status' => 'sent'
            ];
        }

        return Inertia::render('Chat', [
            'interview' => [
                'id' => $interview->id,
                'name' => $interview->name,
                'agent_name' => $interview->agent_name,
                'language' => $interview->language,
                'is_public' => $interview->is_public,
            ],
            'sessionId' => $sessionId,
            'messages' => $messages
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
