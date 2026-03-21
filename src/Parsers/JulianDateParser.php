<?php

namespace DTFormat\PhpDtformat\Parsers;

use DTFormat\PhpDtformat\ParseResult;
use DTFormat\PhpDtformat\Segment;
use Carbon\Carbon;
use Throwable;

/**
 * Julian Date (JD): days since 4713 BC Jan 1 12:00 UTC (JDN 0.5).
 * JD 2440587.5 = 1970-01-01 00:00 UTC. Typical range ~1721426–5373484 (1 AD–9999 AD).
 */
class JulianDateParser implements ParserInterface
{
    private const JD_UNIX_EPOCH = 2440587.5;

    private const SECONDS_PER_DAY = 86400;

    private const MIN_JD = 1721426;

    private const MAX_JD = 5373484;

    public function parse(string $input): ?ParseResult
    {
        $trimmed = trim($input);
        if ($trimmed === '' || ! preg_match('/^\d+(\.\d+)?$/', $trimmed)) {
            return null;
        }

        $jd = (float) $trimmed;
        if ($jd < self::MIN_JD || $jd > self::MAX_JD) {
            return null;
        }

        try {
            $unixSeconds = ($jd - self::JD_UNIX_EPOCH) * self::SECONDS_PER_DAY;
            $carbon = Carbon::createFromTimestamp((int) floor($unixSeconds), 'UTC');
            $microseconds = (int) round(fmod($unixSeconds, 1) * 1_000_000);
            if ($microseconds !== 0) {
                $carbon->addMicroseconds($microseconds);
            }
        } catch (Throwable) {
            return null;
        }

        $mask = (floor($jd) === $jd) ? 'integer' : 'fractional';
        $dot = strpos($trimmed, '.');
        if ($dot !== false) {
            $segments = [
                new Segment('julian_integer', substr($trimmed, 0, $dot), 0, $dot),
                new Segment('julian_fraction', substr($trimmed, $dot), $dot, strlen($trimmed)),
            ];
        } else {
            $segments = [new Segment('julian_integer', $trimmed, 0, strlen($trimmed))];
        }

        return new ParseResult($carbon, 'julian-date', $mask, $segments);
    }
}
