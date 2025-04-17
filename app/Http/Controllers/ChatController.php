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
            'interviewId' => 'required|exists:interviews,id',
            'chatInput' => 'nullable|string', // Make chatInput optional for initialization
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $interview = Interview::findOrFail($request->input('interviewId'));
        
        // Use empty string if chatInput is not provided (for initialization)
        // Empty messages will be treated specially in the InterviewAgent 
        // and won't be added to message history
        $chatInput = $request->input('chatInput', '');
        
        $response = $interviewAgent->chat(
            sessionId: $request->input('sessionId'),
            message: $chatInput,
            interview: $interview
        );

        return response()->json(['output' => $response->structured]);
    }
    
    /**
     * Initialize a new chat session with a welcome message
     */
    public function initialize(Request $request, InterviewAgent $interviewAgent): JsonResponse
    {
        // Validate required fields
        $validator = Validator::make($request->all(), [
            'sessionId' => 'required|string',
            'interviewId' => 'required|exists:interviews,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $interview = Interview::findOrFail($request->input('interviewId'));
        
        // Pass an empty string as the user's message to get just the initial greeting
        $response = $interviewAgent->chat(
            sessionId: $request->input('sessionId'),
            message: '',  // Empty message
            interview: $interview
        );

        return response()->json(['output' => $response->structured]);
    }
}
