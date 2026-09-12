<?php

namespace DTFormat\PhpDtformat\Parsers;

use DTFormat\PhpDtformat\ParseResult;
use DTFormat\PhpDtformat\Segment;
use Carbon\Carbon;
use Throwable;

class ODataDatetimeParser implements ParserInterface
{
    public function parse(string $input): ?ParseResult
    {
        $trimmed = trim($input);
        
        // Handle URL encoded variants like %3A instead of :
        $trimmed = urldecode($trimmed);
        
        // OData Datetime literal: datetime'YYYY-MM-DDTHH:MM:SS'
        // e.g., datetime'2026-08-01T04:00:00'
        if (!preg_match('/^datetime\'(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?)\'$/i', $trimmed, $matches)) {
            return null;
        }

        try {
            $carbon = Carbon::parse($matches[1]);
        } catch (Throwable) {
            return null;
        }

        $segments = [new Segment('odata_datetime', $trimmed, 0, strlen($trimmed))];

        return new ParseResult($carbon, 'odata-datetime', null, $segments);
    }
}
