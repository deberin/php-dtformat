<?php

namespace DTFormat\PhpDtformat\Formatters;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Tire DOT Code: WWYY (week of year + 2-digit year). Week = Sunday–Saturday.
 */
class TireDotFormatter implements FormatterInterface
{
    public function format(CarbonInterface $carbon): string
    {
        $date = $carbon->copy()->startOfDay();
        $year = $date->year;
        $jan1 = Carbon::createFromDate($year, 1, 1);
        $daysToFirstSunday = (7 - $jan1->dayOfWeek) % 7;
        $firstSunday = $jan1->copy()->addDays($daysToFirstSunday);

        if ($date->lt($firstSunday)) {
            $week = 1;
        } else {
            $week = (int) floor($firstSunday->diffInDays($date, false) / 7) + 1;
            if ($week > 53) {
                $week = 53;
            }
        }

        $yy = $year % 100;

        return sprintf('%02d%02d', $week, $yy);
    }
}
