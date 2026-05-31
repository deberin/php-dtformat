<?php

namespace DTFormat\PhpDtformat\Formatters;

use Carbon\CarbonInterface;

class WcfDateFormatter implements FormatterInterface
{
    public function format(CarbonInterface $carbon): string
    {
        // WCF dates are in milliseconds since Unix epoch
        $ms = (int) floor((float) $carbon->format('U.u') * 1000);
        return "/Date({$ms})/";
    }
}
