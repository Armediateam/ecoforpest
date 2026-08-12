<?php

namespace App\Services;

use Carbon\Carbon;

class ShiftTimeHelper
{
    /**
     * Check if an employee is late for their shift, handling overnight shifts correctly.
     * 
     * @param string $clockInTime Format: H:i (e.g., "09:30")
     * @param string $shiftStartTime Format: H:i (e.g., "09:00")
     * @param string $shiftEndTime Format: H:i (e.g., "17:00" or "04:00" for overnight)
     * @param Carbon $clockInDate The date when employee clocked in
     * @return bool True if late, false if on time
     */
    public static function isLate(string $clockInTime, string $shiftStartTime, string $shiftEndTime, Carbon $clockInDate): bool
    {
        $clockInDateTime = Carbon::createFromFormat('Y-m-d H:i', $clockInDate->format('Y-m-d') . ' ' . $clockInTime);
        $shiftStartDateTime = Carbon::createFromFormat('Y-m-d H:i', $clockInDate->format('Y-m-d') . ' ' . $shiftStartTime);

        // Check if this is an overnight shift
        if (self::isOvernightShift($shiftStartTime, $shiftEndTime)) {
            // For overnight shifts, the shift might start the previous day
            // We need to check if the clock in time is closer to yesterday's shift start
            $yesterdayShiftStart = $shiftStartDateTime->copy()->subDay();

            // If clock in time is within 4 hours of midnight, and shift starts after 18:00,
            // then we're probably clocking in for yesterday's night shift
            if ($clockInDateTime->hour < 6 && Carbon::createFromTime(explode(':', $shiftStartTime)[0])->hour >= 18) {
                // Compare with yesterday's shift start
                return $clockInDateTime->gt($yesterdayShiftStart);
            }
        }

        // For regular shifts or day portion of overnight shifts
        return $clockInDateTime->gt($shiftStartDateTime);
    }

    /**
     * Check if a shift is an overnight shift (crosses midnight).
     * 
     * @param string $startTime Format: H:i
     * @param string $endTime Format: H:i
     * @return bool
     */
    public static function isOvernightShift(string $startTime, string $endTime): bool
    {
        $startHour = (int) explode(':', $startTime)[0];
        $endHour = (int) explode(':', $endTime)[0];

        // If start time is later than end time, it's an overnight shift
        return $startHour > $endHour || ($startHour === $endHour && strtotime($startTime) > strtotime($endTime));
    }

    /**
     * Get the effective shift start time for a given date, handling overnight shifts.
     * 
     * @param string $shiftStartTime Format: H:i
     * @param string $shiftEndTime Format: H:i
     * @param Carbon $date The date to check
     * @return Carbon The effective shift start datetime
     */
    public static function getEffectiveShiftStart(string $shiftStartTime, string $shiftEndTime, Carbon $date): Carbon
    {
        $shiftStart = Carbon::createFromFormat('Y-m-d H:i', $date->format('Y-m-d') . ' ' . $shiftStartTime);

        if (self::isOvernightShift($shiftStartTime, $shiftEndTime)) {
            // For overnight shifts, if current time is early morning (before 12:00),
            // the shift actually started yesterday
            if ($date->hour < 12) {
                $shiftStart->subDay();
            }
        }

        return $shiftStart;
    }

    /**
     * Check if a given time falls within a shift's work hours, handling overnight shifts.
     * 
     * @param string $checkTime Format: H:i
     * @param string $shiftStartTime Format: H:i
     * @param string $shiftEndTime Format: H:i
     * @param Carbon $checkDate The date to check
     * @return bool
     */
    public static function isWithinShiftHours(string $checkTime, string $shiftStartTime, string $shiftEndTime, Carbon $checkDate): bool
    {
        $checkDateTime = Carbon::createFromFormat('Y-m-d H:i', $checkDate->format('Y-m-d') . ' ' . $checkTime);
        $shiftStart = self::getEffectiveShiftStart($shiftStartTime, $shiftEndTime, $checkDate);

        if (self::isOvernightShift($shiftStartTime, $shiftEndTime)) {
            // For overnight shifts, end time is next day
            $shiftEnd = Carbon::createFromFormat('Y-m-d H:i', $shiftStart->format('Y-m-d') . ' ' . $shiftEndTime)->addDay();
        } else {
            // For regular shifts, end time is same day
            $shiftEnd = Carbon::createFromFormat('Y-m-d H:i', $shiftStart->format('Y-m-d') . ' ' . $shiftEndTime);
        }

        return $checkDateTime->between($shiftStart, $shiftEnd);
    }

    /**
     * Calculate the duration of a shift in hours, handling overnight shifts.
     * 
     * @param string $startTime Format: H:i
     * @param string $endTime Format: H:i
     * @return float Duration in hours
     */
    public static function getShiftDuration(string $startTime, string $endTime): float
    {
        $start = Carbon::createFromTime(explode(':', $startTime)[0], explode(':', $startTime)[1]);
        $end = Carbon::createFromTime(explode(':', $endTime)[0], explode(':', $endTime)[1]);

        if (self::isOvernightShift($startTime, $endTime)) {
            // For overnight shifts, add 24 hours to end time
            $end->addDay();
        }

        return $start->diffInHours($end, true);
    }
}
