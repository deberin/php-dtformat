<?php

namespace DTFormat\PhpDtformat\Formatters;

use Carbon\CarbonInterface;

/**
 * GPS time: week number + seconds of week since 1980-01-06 00:00:00 UTC.
 */
class GpsTimeFormatter implements FormatterInterface
{
    private const GPS_EPOCH_UNIX = 316054800;
    private const SECONDS_PER_WEEK = 604800;

    public function format(CarbonInterface $carbon): string
    {
        $elapsed = $carbon->timestamp + $carbon->getOffset() - self::GPS_EPOCH_UNIX;
        if ($elapsed < 0) {
            return '0 0';
        }
        $week = (int) floor($elapsed / self::SECONDS_PER_WEEK);
        $seconds = (int) ($elapsed % self::SECONDS_PER_WEEK);

        return $week.' '.$seconds;
    }
}
