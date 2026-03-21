<?php

namespace DTFormat\PhpDtformat\Formatters;

use Carbon\CarbonInterface;

/**
 * NTP timestamp (seconds part): seconds since 1900-01-01 00:00:00 UTC.
 */
class NtpTimestampFormatter implements FormatterInterface
{
    private const NTP_EPOCH_OFFSET = 2208988800;

    public function format(CarbonInterface $carbon): string
    {
        $ntpSeconds = $carbon->timestamp + $carbon->getOffset() + self::NTP_EPOCH_OFFSET;

        return (string) $ntpSeconds;
    }
}
