<?php

namespace DTFormat\PhpDtformat\Formatters;

use Carbon\CarbonInterface;

/** ISO 9075 (SQL) datetime: YYYY-MM-DD HH:MM:SS */
class Iso9075Formatter implements FormatterInterface
{
    public function format(CarbonInterface $carbon): string
    {
        return $carbon->format('Y-m-d H:i:s');
    }
}
