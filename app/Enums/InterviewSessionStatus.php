<?php

namespace App\Enums;

enum InterviewSessionStatus: string
{
    case inProgress = 'in_progress';
    case completed = 'completed';
    case partiallyCompleted = 'partially_completed';
    case canceled = 'canceled';

    /**
     * Get a human-readable label for the status.
     */
    public function label(): string
    {
        return match($this) {
            self::inProgress => 'In Progress',
            self::completed => 'Completed',
            self::partiallyCompleted => 'Partially Completed',
            self::canceled => 'Canceled',
        };
    }

    /**
     * Get all statuses as an array for select inputs.
     */
    public static function toArray(): array
    {
        return [
            self::inProgress->value => self::inProgress->label(),
            self::completed->value => self::completed->label(),
            self::partiallyCompleted->value => self::partiallyCompleted->label(),
            self::canceled->value => self::canceled->label(),
        ];
    }
}
