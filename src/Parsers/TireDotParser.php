<?php

namespace DTFormat\PhpDtformat\Parsers;

use DTFormat\PhpDtformat\ParseResult;
use DTFormat\PhpDtformat\Segment;
use Carbon\Carbon;
use Throwable;

/**
 * Tire DOT Code: WWYY — week (01-53) + 2-digit year.
 * Week = Sunday–Saturday; first week = first Sunday of the year.
 * Year 00-29 → 2000-2029, 30-99 → 1930-1999.
 */
class TireDotParser implements ParserInterface
{
    private const REGEX = '/^(\d{2})(\d{2})$/';

    public function parse(string $input): ?ParseResult
    {
        $trimmed = trim($input);
        if ($trimmed === '' || ! preg_match(self::REGEX, $trimmed, $m, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $week = (int) $m[1][0];
        $yy = (int) $m[2][0];
        if ($week < 1 || $week > 53) {
            return null;
        }

        $year = $yy <= 29 ? 2000 + $yy : 1900 + $yy;

        try {
            $carbon = $this->weekYearToCarbon($year, $week);
        } catch (Throwable) {
            return null;
        }

        $segments = [
            new Segment('tire_week', $m[1][0], $m[1][1], $m[1][1] + 2),
            new Segment('tire_year', $m[2][0], $m[2][1], $m[2][1] + 2),
        ];

        return new ParseResult($carbon, 'tire-dot', null, $segments);
    }

    private function weekYearToCarbon(int $year, int $week): Carbon
    {
        $jan1 = Carbon::createFromDate($year, 1, 1);
        $daysToFirstSunday = (7 - $jan1->dayOfWeek) % 7;
        $firstSunday = $jan1->copy()->addDays($daysToFirstSunday);
        $targetSunday = $firstSunday->addDays(($week - 1) * 7);

        if ($targetSunday->year !== $year) {
            throw new \InvalidArgumentException('Week out of year range');
        }

        return $targetSunday->startOfDay();
    }
}
