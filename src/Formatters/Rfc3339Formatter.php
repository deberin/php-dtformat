<?php

namespace DTFormat\PhpDtformat\Formatters;

use Carbon\CarbonInterface;

class Rfc3339Formatter implements FormatterInterface
{
    public function format(CarbonInterface $carbon): string
    {
        return $carbon->toRfc3339String();
    }
}
