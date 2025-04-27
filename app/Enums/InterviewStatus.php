<?php

namespace App\Enums;

enum InterviewStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Completed = 'completed';
}
