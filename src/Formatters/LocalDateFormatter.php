<?php

namespace DTFormat\PhpDtformat\Formatters;

use Carbon\CarbonInterface;

/**
 * Locale-style date/time: d.m.Y H:i:s.
 */
class LocalDateFormatter implements FormatterInterface
{
    public function format(CarbonInterface $carbon): string
    {
        return $carbon->format('d.m.Y H:i:s');
    }
}
