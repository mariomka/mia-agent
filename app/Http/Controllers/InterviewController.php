<?php

namespace App\Http\Controllers;

use App\Models\Interview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
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

        return Inertia::render('Chat', [
            'interview' => [
                'id' => $interview->id,
                'name' => $interview->name,
                'agent_name' => $interview->agent_name,
                'language' => $interview->language,
                'is_public' => $interview->is_public,
            ]
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
