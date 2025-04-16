<?php

namespace App\Http\Controllers;

use App\Agents\InterviewAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __invoke(Request $request, InterviewAgent $interviewAgent): JsonResponse
    {
        $response = $interviewAgent->chat($request->input('sessionId'), $request->input('chatInput'));

        return response()->json(['output' => $response->structured]);
    }
}
