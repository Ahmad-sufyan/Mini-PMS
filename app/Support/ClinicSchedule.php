<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Facades\Config;

/**
 * The clinic's timezone, opening hours and working days (config/clinic.php).
 *
 * Appointments are stored in UTC; this class converts to and from the
 * clinic's local time, which is what staff see and book in.
 */
class ClinicSchedule
{
    /**
     * Get the clinic's timezone.
     */
    public function timezone(): string
    {
        return Config::string('clinic.timezone');
    }

    /**
     * Get the daily opening time ("H:i").
     */
    public function opensAt(): string
    {
        return Config::string('clinic.opening_hours.opens_at');
    }

    /**
     * Get the daily closing time ("H:i").
     */
    public function closesAt(): string
    {
        return Config::string('clinic.opening_hours.closes_at');
    }

    /**
     * Get the working days as lowercase three-letter day names.
     *
     * @return list<string>
     */
    public function workingDays(): array
    {
        return array_values(array_map(strval(...), Config::array('clinic.working_days')));
    }

    /**
     * Get the current time in the clinic's timezone.
     */
    public function now(): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone());
    }

    /**
     * Get the start of today in the clinic's timezone.
     */
    public function today(): CarbonImmutable
    {
        return $this->now()->startOfDay();
    }

    /**
     * Parse a clinic-local date ("Y-m-d"), returning null when it's invalid.
     */
    public function parseDate(?string $value): ?CarbonImmutable
    {
        if ($value === null || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value, $this->timezone());
        } catch (InvalidFormatException) {
            return null;
        }

        // Reject dates PHP would roll over, such as 2026-02-31.
        return $date?->toDateString() === $value ? $date : null;
    }

    /**
     * Combine a clinic-local date ("Y-m-d") and time ("H:i") into a moment.
     */
    public function at(string $date, string $time): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('!Y-m-d H:i', "{$date} {$time}", $this->timezone())
            ?? throw new InvalidFormatException("Invalid clinic date and time [{$date} {$time}].");
    }

    /**
     * Convert a moment to the clinic's timezone.
     */
    public function toLocal(CarbonInterface $moment): CarbonImmutable
    {
        return CarbonImmutable::instance($moment)->setTimezone($this->timezone());
    }

    /**
     * Determine if the clinic is open on the (local) day of the given moment.
     */
    public function isWorkingDay(CarbonInterface $moment): bool
    {
        return in_array(strtolower($this->toLocal($moment)->format('D')), $this->workingDays(), true);
    }

    /**
     * Determine if an appointment from start to end falls within one day's
     * opening hours on a working day.
     */
    public function isWithinOpeningHours(CarbonInterface $start, CarbonInterface $end): bool
    {
        $localStart = $this->toLocal($start);
        $day = $localStart->startOfDay();

        return $this->isWorkingDay($localStart)
            && $localStart->gte($day->setTimeFromTimeString($this->opensAt()))
            && $this->toLocal($end)->lte($day->setTimeFromTimeString($this->closesAt()));
    }

    /**
     * Get the first working day on or after the given local day.
     */
    public function nextWorkingDay(CarbonImmutable $day): CarbonImmutable
    {
        $day = $this->toLocal($day)->startOfDay();

        for ($i = 0; $i < 7 && ! $this->isWorkingDay($day); $i++) {
            $day = $day->addDay();
        }

        return $day;
    }

    /**
     * Get the schedule details shared with the frontend.
     *
     * @return array{timezone: string, opens_at: string, closes_at: string, working_days: list<string>}
     */
    public function toArray(): array
    {
        return [
            'timezone' => $this->timezone(),
            'opens_at' => $this->opensAt(),
            'closes_at' => $this->closesAt(),
            'working_days' => $this->workingDays(),
        ];
    }
}
