<?php

namespace DTFormat\PhpDtformat\Parsers;

use DTFormat\PhpDtformat\ParseResult;
use DTFormat\PhpDtformat\Segment;
use Carbon\Carbon;
use Throwable;

/**
 * Excel serial date: days since 1900-01-01 (serial 1), with 1900 leap-year bug.
 * Fractional part = time of day. Valid range 1..2958465 (9999-12-31).
 */
class ExcelSerialParser implements ParserInterface
{
    private const MIN_SERIAL = 1;

    private const MAX_SERIAL = 2958465;

    public function parse(string $input): ?ParseResult
    {
        $trimmed = trim($input);
        if ($trimmed === '' || ! preg_match('/^-?\d+(\.\d+)?$/', $trimmed)) {
            return null;
        }

        $serial = (float) $trimmed;
        if ($serial < self::MIN_SERIAL || $serial > self::MAX_SERIAL) {
            return null;
        }

        try {
            $carbon = $this->serialToCarbon($serial);
        } catch (Throwable) {
            return null;
        }

        $mask = (floor($serial) === $serial) ? 'date' : 'datetime';
        $dot = strpos($trimmed, '.');
        if ($dot !== false) {
            $segments = [
                new Segment('serial_days', substr($trimmed, 0, $dot), 0, $dot),
                new Segment('serial_time_fraction', substr($trimmed, $dot), $dot, strlen($trimmed)),
            ];
        } else {
            $segments = [new Segment('serial_days', $trimmed, 0, strlen($trimmed))];
        }

        return new ParseResult($carbon, 'excel-serial', $mask, $segments);
    }

    /** Excel serial 1 = 1900-01-01; base 1899-12-31. Serial >= 61 skips fake 1900-02-29. */
    private function serialToCarbon(float $serial): Carbon
    {
        $days = (int) floor($serial);
        $frac = fmod($serial, 1);
        if ($frac < 0) {
            $frac += 1;
        }

        $daysToAdd = $days >= 61 ? $days - 1 : $days;
        $carbon = Carbon::createFromDate(1899, 12, 31)->addDays($daysToAdd)->startOfDay();
        $carbon->addSeconds((int) round($frac * 86400));

        return $carbon;
    }
}
