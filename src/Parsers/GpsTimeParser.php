<?php

namespace DTFormat\PhpDtformat\Parsers;

use DTFormat\PhpDtformat\ParseResult;
use DTFormat\PhpDtformat\Segment;
use Carbon\Carbon;
use Throwable;

/**
 * GPS time: week number + seconds of week. Epoch: 1980-01-06 00:00:00 UTC.
 * Formats: "WEEK SECONDS", "WEEK.SECONDS", or "WEEK" (seconds = 0).
 */
class GpsTimeParser implements ParserInterface
{
    private const GPS_EPOCH_UNIX = 316054800;

    private const SECONDS_PER_WEEK = 604800;

    public function parse(string $input): ?ParseResult
    {
        $trimmed = trim($input);
        if ($trimmed === '') {
            return null;
        }

        $secondsOfWeek = 0;
        $segments = [];
        if (preg_match('/^(\d{1,4})([\s.]+)(\d{1,7})$/', $trimmed, $m, PREG_OFFSET_CAPTURE)) {
            $week = (int) $m[1][0];
            $secondsOfWeek = (int) $m[3][0];
            $segments = [
                new Segment('gps_week', $m[1][0], $m[1][1], $m[1][1] + strlen($m[1][0])),
                new Segment('gps_seconds_of_week', $m[3][0], $m[3][1], $m[3][1] + strlen($m[3][0])),
            ];
        } elseif (preg_match('/^\d{1,4}$/', $trimmed)) {
            $week = (int) $trimmed;
            $segments = [new Segment('gps_week', $trimmed, 0, strlen($trimmed))];
        } else {
            return null;
        }

        if ($week < 0 || $week > 4095 || $secondsOfWeek < 0 || $secondsOfWeek >= self::SECONDS_PER_WEEK) {
            return null;
        }

        try {
            $unixSeconds = self::GPS_EPOCH_UNIX + $week * self::SECONDS_PER_WEEK + $secondsOfWeek;
            $carbon = Carbon::createFromTimestamp($unixSeconds, 'UTC');
        } catch (Throwable) {
            return null;
        }

        return new ParseResult($carbon, 'gps-time', null, $segments);
    }
}
