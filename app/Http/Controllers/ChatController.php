<?php

namespace App\Http\Controllers;

use App\Agents\InterviewAgent;
use App\Models\Interview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __invoke(Request $request, InterviewAgent $interviewAgent): JsonResponse
    {
        $interview = Interview::findOrFail($request->input('interviewId'));
        
        $response = $interviewAgent->chat(
            sessionId: $request->input('sessionId'),
            message: $request->input('chatInput'),
            interview: $interview
        );

        return response()->json(['output' => $response->structured]);
    }
}
