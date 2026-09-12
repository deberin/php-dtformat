<?php

namespace DTFormat\PhpDtformat\Formatters;

use Carbon\CarbonInterface;

class ODataDatetimeFormatter implements FormatterInterface
{
    public function format(CarbonInterface $carbon): string
    {
        return "datetime'{$carbon->format('Y-m-d\TH:i:s')}'";
    }
}
