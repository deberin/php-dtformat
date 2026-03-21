<?php

namespace DTFormat\PhpDtformat\Formatters;

use Carbon\CarbonInterface;

/**
 * Julian Date (JD): days since 4713 BC Jan 1 12:00 UTC.
 */
class JulianDateFormatter implements FormatterInterface
{
    private const JD_UNIX_EPOCH = 2440587.5;
    private const SECONDS_PER_DAY = 86400;

    public function format(CarbonInterface $carbon): string
    {
        $unixSeconds = $carbon->timestamp;
        $jd = $unixSeconds / self::SECONDS_PER_DAY + self::JD_UNIX_EPOCH;

        return $jd === floor($jd) ? (string) (int) $jd : (string) round($jd, 6);
    }
}
