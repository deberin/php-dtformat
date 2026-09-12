<?php

namespace DTFormat\PhpDtformat\Formatters;

use Carbon\CarbonInterface;

class OracleTimestampFormatter implements FormatterInterface
{
    public function format(CarbonInterface $carbon): string
    {
        $amPm = $carbon->format('A');
        $date = strtoupper($carbon->format('d-M-y'));
        $time = $carbon->format('h.i.s');
        
        $micro = $carbon->micro;
        if ($micro > 0) {
            $microStr = str_pad((string)$micro, 9, '0', STR_PAD_RIGHT);
            return "{$date} {$time}.{$microStr} {$amPm}";
        }
        
        return "{$date} {$time} {$amPm}";
    }
}
