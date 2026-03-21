<?php

namespace DTFormat\PhpDtformat\Formatters;

use Carbon\CarbonInterface;

/**
 * .NET Ticks: 100-nanosecond intervals since 0001-01-01 00:00:00 UTC.
 */
class DotNetTicksFormatter implements FormatterInterface
{
    private const TICKS_PER_SECOND = 10_000_000;
    private const UNIX_EPOCH_TICKS = 621355968000000000;

    public function format(CarbonInterface $carbon): string
    {
        $unixSeconds = $carbon->timestamp + $carbon->getOffset();
        $micros = $carbon->micro;
        $epochSeconds = (int) (self::UNIX_EPOCH_TICKS / self::TICKS_PER_SECOND);
        $ticks = ($unixSeconds + $epochSeconds) * self::TICKS_PER_SECOND + (int) round($micros * 10);

        return (string) $ticks;
    }
}
