<?php

namespace DTFormat\PhpDtformat\Formatters;

use Carbon\CarbonInterface;

class OdbcTimestampFormatter implements FormatterInterface
{
    public function format(CarbonInterface $carbon): string
    {
        return "{ts '{$carbon->format('Y-m-d H:i:s')}'}";
    }
}
