<?php

namespace DTFormat\PhpDtformat\Formatters;

use Carbon\CarbonInterface;

/**
 * Chinese date: YYYY年M月D日 [HH时MM分]
 */
class ChineseDateFormatter implements FormatterInterface
{
    public function format(CarbonInterface $carbon): string
    {
        $date = $carbon->format('Y年n月j日');
        if ($carbon->hour !== 0 || $carbon->minute !== 0 || $carbon->second !== 0) {
            $date .= ' ' . $carbon->format('G时i分');
        }

        return $date;
    }
}
