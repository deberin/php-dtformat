<?php

namespace DTFormat\PhpDtformat\Formatters;

use Carbon\CarbonInterface;

/**
 * Mac (HFS+) timestamp: seconds since 1904-01-01 00:00:00 UTC.
 */
class MacTimestampFormatter implements FormatterInterface
{
    private const MAC_EPOCH_OFFSET = 2082844800;

    public function format(CarbonInterface $carbon): string
    {
        $macSeconds = $carbon->timestamp + self::MAC_EPOCH_OFFSET;

        return (string) $macSeconds;
    }
}
