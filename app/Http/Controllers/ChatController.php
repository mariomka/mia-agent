<?php

namespace App\Http\Controllers;

use App\Agents\InterviewAgent;
use App\Models\Interview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    public function __invoke(Request $request, InterviewAgent $interviewAgent): JsonResponse
    {
        // Validate required fields
        $validator = Validator::make($request->all(), [
            'sessionId' => 'required|string',
            'chatInput' => 'required|string',
            'interviewId' => 'required|exists:interviews,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $interview = Interview::findOrFail($request->input('interviewId'));
        
        $response = $interviewAgent->chat(
            sessionId: $request->input('sessionId'),
            message: $request->input('chatInput'),
            interview: $interview
        );

        return response()->json(['output' => $response->structured]);
    }
}
