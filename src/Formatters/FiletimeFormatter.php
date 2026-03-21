<?php

namespace DTFormat\PhpDtformat\Formatters;

use Carbon\CarbonInterface;

/**
 * Windows Filetime: 100-nanosecond intervals since 1601-01-01 00:00:00 UTC.
 */
class FiletimeFormatter implements FormatterInterface
{
    private const TICKS_PER_SECOND = 10_000_000;
    private const EPOCH_OFFSET_SECONDS = 11644473600;

    public function format(CarbonInterface $carbon): string
    {
        $unixSeconds = $carbon->timestamp;
        $seconds = $unixSeconds + self::EPOCH_OFFSET_SECONDS;
        $ticks = (int) round($seconds * self::TICKS_PER_SECOND);

        return (string) $ticks;
    }
}
