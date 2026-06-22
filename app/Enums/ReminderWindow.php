<?php

namespace App\Enums;

enum ReminderWindow: string
{
    case ThreeDay = '3d';
    case OneDay = '24h';

    /** Human lead time used in the reminder email copy — the one place it's decided. */
    public function lead(): string
    {
        return match ($this) {
            self::ThreeDay => '3 days',
            self::OneDay => '24 hours',
        };
    }
}
