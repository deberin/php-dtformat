<?php

namespace DTFormat\PhpDtformat\Formatters;

use Carbon\CarbonInterface;

class PdfDateFormatter implements FormatterInterface
{
    public function format(CarbonInterface $carbon): string
    {
        // PDF format: D:YYYYMMDDHHmmSSOHH'mm'
        $formatted = $carbon->format('YmdHis');
        
        $offsetMinutes = $carbon->getOffset() / 60;
        
        if ($offsetMinutes === 0) {
            $formatted .= 'Z';
        } else {
            $sign = $offsetMinutes < 0 ? '-' : '+';
            $absMinutes = abs($offsetMinutes);
            $hours = floor($absMinutes / 60);
            $minutes = $absMinutes % 60;
            $formatted .= sprintf("%s%02d'%02d'", $sign, $hours, $minutes);
        }

        return 'D:' . $formatted;
    }
}
