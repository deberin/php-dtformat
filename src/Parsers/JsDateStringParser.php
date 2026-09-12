<?php

namespace DTFormat\PhpDtformat\Parsers;

use DTFormat\PhpDtformat\ParseResult;
use DTFormat\PhpDtformat\Segment;
use Carbon\Carbon;
use Throwable;

class JsDateStringParser implements ParserInterface
{
    public function parse(string $input): ?ParseResult
    {
        $trimmed = trim($input, " \t\n\r\0\x0B\"'");
        
        // JS Date.toString() format: Day Mon DD YYYY HH:mm:ss GMT+XXXX (Timezone Name)
        // e.g., Mon Dec 01 2026 17:07:18 GMT-0700 (Pacific Daylight Time)
        // or Sun Aug 09 14:38:59 EDT 2026
        if (!preg_match('/^[a-z]{3}\s+[a-z]{3}\s+\d{2}\s+\d{4}\s+\d{2}:\d{2}:\d{2}\s+GMT[+-]\d{4}/i', $trimmed) &&
            !preg_match('/^[a-z]{3}\s+[a-z]{3}\s+\d{2}\s+\d{2}:\d{2}:\d{2}\s+[a-z]{3,4}\s+\d{4}/i', $trimmed) &&
            !preg_match('/^[a-z]{3}\s+[a-z]{3}\s+\d{2}\s+\d{4}\s+\d{2}:\d{2}:\d{2}/i', $trimmed)
        ) {
            return null;
        }

        try {
            // Carbon can generally parse this format natively, but we might need to strip the timezone name in parentheses
            $cleanString = preg_replace('/\s+\(.*\)$/', '', $trimmed);
            $carbon = Carbon::parse($cleanString);
        } catch (Throwable) {
            return null;
        }

        $segments = [new Segment('js_date_string', $trimmed, 0, strlen($trimmed))];

        return new ParseResult($carbon, 'js-date-string', null, $segments);
    }
}
