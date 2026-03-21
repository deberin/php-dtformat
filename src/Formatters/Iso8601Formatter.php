<?php

namespace DTFormat\PhpDtformat\Formatters;

use Carbon\CarbonInterface;

class Iso8601Formatter implements FormatterInterface
{
    public function format(CarbonInterface $carbon): string
    {
        return $carbon->toIso8601String();
    }
}
