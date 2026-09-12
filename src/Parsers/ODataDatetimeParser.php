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
        if (!preg_match('/^datetime\'(?P<year>\d{4})-(?P<month>\d{2})-(?P<day>\d{2})T(?P<hour>\d{2}):(?P<minute>\d{2}):(?P<second>\d{2})(?:\.(?P<millisecond>\d+))?\'$/i', $trimmed, $matches, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        try {
            $carbon = Carbon::parse($matches['year'][0] . '-' . $matches['month'][0] . '-' . $matches['day'][0] . 'T' . $matches['hour'][0] . ':' . $matches['minute'][0] . ':' . $matches['second'][0] . (isset($matches['millisecond']) && $matches['millisecond'][1] >= 0 ? '.' . $matches['millisecond'][0] : ''));
        } catch (Throwable) {
            return null;
        }

        $segments = \DTFormat\PhpDtformat\ParseSegmentBuilder::fromNamedMatch($matches, [
            'year' => 'year',
            'month' => 'month',
            'day' => 'day',
            'hour' => 'hour',
            'minute' => 'minute',
            'second' => 'second',
            'millisecond' => 'millisecond',
        ]);

        return new ParseResult($carbon, 'odata-datetime', null, $segments);
    }
}
