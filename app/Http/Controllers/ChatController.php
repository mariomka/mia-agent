<?php

namespace App\Http\Controllers;

use App\Agents\InterviewAgent;
use App\Enums\InterviewStatus;
use App\Models\Interview;
use App\Models\InterviewSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    public function __invoke(Request $request, InterviewAgent $interviewAgent): JsonResponse
    {
        // Validate required fields
        $validator = Validator::make($request->all(), [
            'sessionId' => 'required|string',
            'interviewId' => 'required|exists:interviews,id',
            'chatInput' => 'nullable|string|max:200',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $sessionId = $request->input('sessionId');
        $interview = Interview::findOrFail($request->input('interviewId'));

        if (!Auth::check() && $interview->status === InterviewStatus::Draft) {
            return response()->json([
                'error' => 'Interview not found or not available.',
            ], 404);
        }

        if ($interview->status === InterviewStatus::Completed) {
            return response()->json([
                'error' => 'Interview not found or not available.',
            ], 404);
        }

        $session = InterviewSession::where('id', $sessionId)
            ->where('interview_id', $interview->id)
            ->first();

        if ($session && $session->finished) {
            return response()->json([
                'error' => 'This interview is already completed and cannot accept new messages.',
                'finished' => true
            ], 400);
        }

        // Use empty string if chatInput is not provided (for initialization)
        // Empty messages will be treated specially in the InterviewAgent
        // and won't be added to message history
        $chatInput = $request->input('chatInput', '');

        $output = $interviewAgent->chat(
            sessionId: $sessionId,
            message: $chatInput,
            interview: $interview
        );

        // Filter the response to only include messages and finished status
        $filteredOutput = [
            'messages' => $output['messages'] ?? [],
            'finished' => $output['finished'] ?? false,
        ];

        return response()->json(['output' => $filteredOutput]);
    }
}
