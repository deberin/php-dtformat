<?php

namespace DTFormat\PhpDtformat\Formatters;

use Carbon\CarbonInterface;

class Rfc2822Formatter implements FormatterInterface
{
    public function format(CarbonInterface $carbon): string
    {
        return $carbon->toRfc2822String();
    }
}
