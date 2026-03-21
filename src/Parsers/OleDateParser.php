<?php

namespace DTFormat\PhpDtformat\Parsers;

use DTFormat\PhpDtformat\ParseResult;
use DTFormat\PhpDtformat\Segment;
use Carbon\Carbon;
use Throwable;

/**
 * OLE Automation Date (VBA/COM): days since 1899-12-30 00:00:00.
 * Fractional part = time of day. 0 = 1899-12-30, 1 = 1899-12-31, 2 = 1900-01-01.
 */
class OleDateParser implements ParserInterface
{
    private const MAX_DAYS = 2958466;

    public function parse(string $input): ?ParseResult
    {
        $trimmed = trim($input);
        if ($trimmed === '' || ! preg_match('/^-?\d+(\.\d+)?$/', $trimmed)) {
            return null;
        }

        $serial = (float) $trimmed;
        if ($serial < 0 || $serial > self::MAX_DAYS) {
            return null;
        }

        try {
            $days = (int) floor($serial);
            $frac = fmod($serial, 1);
            if ($frac < 0) {
                $frac += 1;
            }
            $carbon = Carbon::createFromDate(1899, 12, 30)->addDays($days)->startOfDay();
            $carbon->addSeconds((int) round($frac * 86400));
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

        return new ParseResult($carbon, 'ole-date', $mask, $segments);
    }
}
