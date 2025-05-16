<?php

namespace App\Http\Controllers;

use App\Enums\InterviewSessionStatus;
use App\Enums\InterviewStatus;
use App\Models\Interview;
use App\Models\InterviewSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InterviewController extends Controller
{
    public function __invoke(Request $request, Interview $interview): Response
    {
        if (!Auth::check() && $interview->status === InterviewStatus::Draft) {
            abort(404);
        }

        if ($interview->status === InterviewStatus::Completed) {
            abort(404);
        }

        $interviewSessionKey = "interview_{$interview->id}_session_id";

        $sessionId = $request->session()->get($interviewSessionKey);

        if ($sessionId) {
            $session = InterviewSession::find($sessionId);

            if ($session === null) {
                $sessionId = null;
            }
        }

        if ($sessionId === null) {
            $session = InterviewSession::create([
                'interview_id' => $interview->id,
                // Ensure default values are set if creating
                'messages' => [],
                'metadata' => ['query_parameters' => $request->query()],
                'status' => InterviewSessionStatus::inProgress,
            ]);
        }

        $request->session()->put($interviewSessionKey, $session->id);

        $messages = [];
        foreach ($session->messages as $index => $message) {
            $messages[] = [
                'id' => "{$session->id}_{$index}", // Use session->id for consistency
                'sender' => $message['type'] === 'assistant' ? 'ai' : $message['type'],
                'text' => $message['content'],
                'status' => 'sent'
            ];
        }

        // Send data to frontend
        return Inertia::render('Chat', [
            'interview' => [
                'id' => $interview->id,
                'name' => $interview->name,
                'agent_name' => $interview->agent_name,
                'language' => $interview->language,
                'company_name' => $interview->company_name,
                'product_name' => $interview->product_name,
                'product_description' => $interview->product_description,
            ],
            'sessionId' => $session->id, // Use the definitive session ID
            'messages' => $messages,
            'is_finished' => $session->status !== InterviewSessionStatus::inProgress
        ]);
    }

    public static function generateUrl(Interview $interview, ?array $params = []): string
    {
        return route('interview', [
            'interview' => $interview,
            ...$params
        ]);
    }
}
