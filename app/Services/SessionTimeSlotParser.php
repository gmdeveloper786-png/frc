<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Parses recurring/display time_slot strings (e.g. "9:00AM - 9:30AM") for session-window checks.
 *
 * Mirrors behaviour previously embedded in {@see ChildPortalService}.
 */
final class SessionTimeSlotParser
{
    /**
     * @return array{hour: int, minute: int}
     */
    public static function parseStart(string $timeSlot): array
    {
        $slot = trim($timeSlot);

        if (preg_match('/(\d{1,2}):(\d{2})\s*(am|pm)?/i', $slot, $m)) {
            return self::to24Hour((int) $m[1], (int) $m[2], strtolower($m[3] ?? ''));
        }

        return ['hour' => 9, 'minute' => 0];
    }

    /**
     * End time from strings like "10:30AM - 11:00AM". Falls back to start + 30 minutes.
     *
     * @return array{hour: int, minute: int}
     */
    public static function parseEnd(string $timeSlot): array
    {
        $slot = trim($timeSlot);

        if (preg_match('/-\s*(\d{1,2}):(\d{2})\s*(am|pm)?/i', $slot, $m)) {
            return self::to24Hour((int) $m[1], (int) $m[2], strtolower($m[3] ?? ''));
        }

        $start = self::parseStart($timeSlot);
        $endMinute = $start['minute'] + 30;
        $endHour = $start['hour'] + intdiv($endMinute, 60);
        $endMinute %= 60;
        $endHour %= 24;

        return ['hour' => $endHour, 'minute' => $endMinute];
    }

    /**
     * @return array{hour: int, minute: int}
     */
    private static function to24Hour(int $hour, int $minute, string $ampm): array
    {
        if ($ampm === 'pm' && $hour < 12) {
            $hour += 12;
        }
        if ($ampm === 'am' && $hour === 12) {
            $hour = 0;
        }

        return ['hour' => $hour, 'minute' => $minute];
    }

    /**
     * Start instant for this occurrence: calendar date + first time in the application timezone.
     * Slot strings like "4:00PM - 4:30PM" are wall-clock times in that timezone (see APP_TIMEZONE).
     *
     * @param  Carbon|string  $sessionDay  Date-only or Carbon whose Y-m-d is used.
     */
    public static function occurrenceStart(Carbon|string $sessionDay, string $timeSlot): Carbon
    {
        $tz = config('app.timezone');
        $dateIso = $sessionDay instanceof Carbon ? $sessionDay->format('Y-m-d') : (string) $sessionDay;
        $p = self::parseStart($timeSlot);

        return Carbon::parse($dateIso, $tz)->startOfDay()->setTime($p['hour'], $p['minute'], 0);
    }

    /**
     * End instant for this occurrence (exclusive window: start allowed only while now < end).
     *
     * @param  Carbon|string  $sessionDay  Date-only or Carbon whose Y-m-d is used.
     */
    public static function occurrenceEnd(Carbon|string $sessionDay, string $timeSlot): Carbon
    {
        $tz = config('app.timezone');
        $dateIso = $sessionDay instanceof Carbon ? $sessionDay->format('Y-m-d') : (string) $sessionDay;
        $p = self::parseEnd($timeSlot);

        return Carbon::parse($dateIso, $tz)->startOfDay()->setTime($p['hour'], $p['minute'], 0);
    }

    /** Whether the current moment is within [start, end) for this occurrence. */
    public static function isWithinStartWindow(Carbon|string $sessionDay, string $timeSlot, ?Carbon $at = null): bool
    {
        $at ??= now();
        $startsAt = self::occurrenceStart($sessionDay, $timeSlot);
        $endsAt = self::occurrenceEnd($sessionDay, $timeSlot);

        return $at->greaterThanOrEqualTo($startsAt) && $at->lt($endsAt);
    }

    /** Calendar date of the occurrence at start-of-day in the application timezone. */
    public static function sessionDayStart(Carbon|string $sessionDay): Carbon
    {
        $tz = config('app.timezone');
        $dateIso = $sessionDay instanceof Carbon ? $sessionDay->format('Y-m-d') : (string) $sessionDay;

        return Carbon::parse($dateIso, $tz)->startOfDay();
    }

    /** True when "now" falls on the same calendar day as the session (APP_TIMEZONE). */
    public static function isSessionDayToday(Carbon|string $sessionDay, ?Carbon $at = null): bool
    {
        $at ??= now();
        $tz = config('app.timezone');

        return $at->copy()->timezone($tz)->startOfDay()->equalTo(self::sessionDayStart($sessionDay));
    }

    /** True when the session calendar day is before today (APP_TIMEZONE). */
    public static function isSessionDayPast(Carbon|string $sessionDay, ?Carbon $at = null): bool
    {
        $at ??= now();
        $tz = config('app.timezone');

        return $at->copy()->timezone($tz)->startOfDay()->greaterThan(self::sessionDayStart($sessionDay));
    }
}
