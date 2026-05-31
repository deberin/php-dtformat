<?php

namespace DTFormat\PhpDtformat\Parsers;

use DTFormat\PhpDtformat\ParseResult;
use DTFormat\PhpDtformat\Segment;
use Carbon\Carbon;
use Throwable;

/**
 * Microsoft JSON Date (WCF Date)
 * Formats: /Date(1777667541000)/, \/Date(1788238800000)\/, /Date(1198908717056-0700)/
 */
class WcfDateParser implements ParserInterface
{
    public function parse(string $input): ?ParseResult
    {
        $trimmed = trim($input);
        
        // Match /Date(1234567890)/ or \/Date(1234567890)\/ with optional timezone
        if (!preg_match('/^\\\\?\/Date\((-?\d+)([+-]\d{4})?\)\\\\?\/$/i', $trimmed, $matches)) {
            return null;
        }

        try {
            $msValue = (int) $matches[1];
            $seconds = (int) floor($msValue / 1000);
            $milliseconds = $msValue % 1000;
            if ($milliseconds < 0) {
                $milliseconds += 1000;
            }

            $carbon = Carbon::createFromTimestamp($seconds);
            if ($milliseconds > 0) {
                $carbon->setMilliseconds($milliseconds);
            }
        } catch (Throwable) {
            return null;
        }

        $segments = [new Segment('wcf_date', $trimmed, 0, strlen($trimmed))];

        return new ParseResult($carbon, 'wcf-date', null, $segments);
    }
}
