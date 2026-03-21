<?php

namespace DTFormat\PhpDtformat\Formatters;

use Carbon\CarbonInterface;

class UnixSecondsFormatter implements FormatterInterface
{
    public function format(CarbonInterface $carbon): string
    {
        return (string) $carbon->timestamp;
    }
}
