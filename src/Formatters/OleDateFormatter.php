<?php

namespace DTFormat\PhpDtformat\Formatters;

use Carbon\CarbonInterface;

/**
 * OLE Automation Date: days since 1899-12-30 00:00:00, fraction = time of day.
 */
class OleDateFormatter implements FormatterInterface
{
    private const OLE_EPOCH_UNIX = -2209161600;

    public function format(CarbonInterface $carbon): string
    {
        $unix = $carbon->timestamp + $carbon->getOffset();
        $days = ($unix - self::OLE_EPOCH_UNIX) / 86400;
        $frac = fmod($days, 1);
        if ($frac < 0) {
            $frac += 1;
        }
        $serial = $days;

        return $serial === floor($serial) ? (string) (int) $serial : (string) round($serial, 6);
    }
}
