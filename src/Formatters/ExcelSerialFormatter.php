<?php

namespace DTFormat\PhpDtformat\Formatters;

use Carbon\CarbonInterface;

/**
 * Excel serial date: days since 1900-01-01 (serial 1), with 1900 leap-year bug.
 */
class ExcelSerialFormatter implements FormatterInterface
{
    private const EXCEL_EPOCH = 25569; // 1970-01-01 in Excel (days since 1900-01-01 with bug)

    public function format(CarbonInterface $carbon): string
    {
        $unix = $carbon->timestamp + $carbon->getOffset();
        $days = floor($unix / 86400) + self::EXCEL_EPOCH;
        $frac = ($unix % 86400) / 86400;
        if ($frac < 0) {
            $frac += 1;
        }

        $serial = $days + $frac;

        return $serial === floor($serial) ? (string) (int) $serial : (string) round($serial, 6);
    }
}
