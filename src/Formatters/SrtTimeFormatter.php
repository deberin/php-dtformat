<?php

namespace DTFormat\PhpDtformat\Formatters;

use Carbon\CarbonInterface;

class SrtTimeFormatter implements FormatterInterface
{
    public function format(CarbonInterface $carbon): string
    {
        $time = $carbon->format('H:i:s');
        // Pad microseconds to 3 digits for milliseconds
        $ms = str_pad((string)(int)round($carbon->micro / 1000), 3, '0', STR_PAD_LEFT);
        return "{$time},{$ms}";
    }
}
