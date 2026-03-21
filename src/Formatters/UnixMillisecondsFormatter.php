<?php

namespace DTFormat\PhpDtformat\Formatters;

use Carbon\CarbonInterface;

class UnixMillisecondsFormatter implements FormatterInterface
{
    public function format(CarbonInterface $carbon): string
    {
        return (string) ($carbon->timestamp * 1000 + (int) ($carbon->millisecond));
    }
}
