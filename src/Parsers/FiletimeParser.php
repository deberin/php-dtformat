<?php

namespace DTFormat\PhpDtformat\Parsers;

use DTFormat\PhpDtformat\ParseResult;
use DTFormat\PhpDtformat\Segment;
use Carbon\Carbon;
use Throwable;

/**
 * Windows Filetime: 100-nanosecond intervals since 1601-01-01 00:00:00 UTC.
 * 64-bit unsigned; typical range 10–19 digits.
 */
class FiletimeParser implements ParserInterface
{
    private const TICKS_PER_SECOND = 10_000_000;

    private const EPOCH_OFFSET_SECONDS = 11644473600; // 1601-01-01 to 1970-01-01 UTC

    public function parse(string $input): ?ParseResult
    {
        $trimmed = trim($input);
        if ($trimmed === '' || ! ctype_digit($trimmed)) {
            return null;
        }

        $len = strlen($trimmed);
        if ($len < 14 || $len > 19) {
            return null;
        }

        $ticks = (float) $trimmed;
        if ($ticks > 9.22e18) {
            return null;
        }

        try {
            $unixSeconds = ($ticks / self::TICKS_PER_SECOND) - self::EPOCH_OFFSET_SECONDS;
            $carbon = Carbon::createFromTimestamp((int) floor($unixSeconds), 'UTC');
            $microseconds = (int) round(fmod($unixSeconds, 1) * 1_000_000);
            if ($microseconds !== 0) {
                $carbon->addMicroseconds($microseconds);
            }
        } catch (Throwable) {
            return null;
        }

        $segments = [new Segment('filetime_ticks', $trimmed, 0, $len)];

        return new ParseResult($carbon, 'filetime', null, $segments);
    }
}
