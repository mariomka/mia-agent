<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Chat');
    }
}
