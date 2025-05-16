<?php

namespace App\Enums;

enum InterviewSessionStatus: string
{
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case PARTIALLY_COMPLETED = 'partially_completed';
    case CANCELED = 'canceled';

    /**
     * Get a human-readable label for the status.
     */
    public function label(): string
    {
        return match($this) {
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::PARTIALLY_COMPLETED => 'Partially Completed',
            self::CANCELED => 'Canceled',
        };
    }

    /**
     * Get all statuses as an array for select inputs.
     */
    public static function toArray(): array
    {
        return [
            self::IN_PROGRESS->value => self::IN_PROGRESS->label(),
            self::COMPLETED->value => self::COMPLETED->label(),
            self::PARTIALLY_COMPLETED->value => self::PARTIALLY_COMPLETED->label(),
            self::CANCELED->value => self::CANCELED->label(),
        ];
    }
}
