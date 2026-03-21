<?php

namespace DTFormat\PhpDtformat\Formatters;

use Carbon\CarbonInterface;

/**
 * Human-readable text date format.
 * Examples: Jan 16, 2026 23:40:48 / January 16th 2026, 11:40:48 pm
 * Translates locally if possible.
 */
class TextDateFormatter implements FormatterInterface
{
    public function format(CarbonInterface $carbon): string
    {
        // Carbon::translatedFormat automatically uses App::getLocale()
        return $carbon->translatedFormat('F j, Y, g:i:s A');
    }
}
