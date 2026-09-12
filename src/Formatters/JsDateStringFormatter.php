<?php

namespace DTFormat\PhpDtformat\Formatters;

use Carbon\CarbonInterface;

class JsDateStringFormatter implements FormatterInterface
{
    public function format(CarbonInterface $carbon): string
    {
        // format: Mon Dec 01 2026 17:07:18 GMT-0700
        // D M d Y H:i:s \G\M\TO
        return $carbon->format('D M d Y H:i:s \G\M\TO');
    }
}
