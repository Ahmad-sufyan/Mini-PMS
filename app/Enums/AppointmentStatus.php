<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AppointmentStatus: string
{
    use HasOptions;

    case Scheduled = 'scheduled';
    case CheckedIn = 'checked_in';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    /**
     * Get the human readable name of the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::CheckedIn => 'Checked in',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::NoShow => 'No-show',
        };
    }

    /**
     * Get the label of the button that moves an appointment to this status.
     */
    public function actionLabel(): string
    {
        return match ($this) {
            self::Scheduled => 'Schedule',
            self::CheckedIn => 'Check in',
            self::Completed => 'Complete',
            self::Cancelled => 'Cancel',
            self::NoShow => 'No-show',
        };
    }

    /**
     * Get the statuses an appointment can move to from this one:
     * scheduled -> checked in -> completed, or scheduled -> cancelled / no-show.
     *
     * @return list<self>
     */
    public function nextStatuses(): array
    {
        return match ($this) {
            self::Scheduled => [self::CheckedIn, self::Cancelled, self::NoShow],
            self::CheckedIn => [self::Completed],
            self::Completed, self::Cancelled, self::NoShow => [],
        };
    }

    /**
     * Determine if an appointment can move from this status to the given one.
     */
    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->nextStatuses(), true);
    }

    /**
     * Get the statuses that take up the doctor's time. Cancelled and
     * no-show appointments free the slot for someone else.
     *
     * @return list<self>
     */
    public static function occupying(): array
    {
        return [self::Scheduled, self::CheckedIn, self::Completed];
    }
}
