<?php

namespace DTFormat\PhpDtformat\Parsers;

use DTFormat\PhpDtformat\ParseResult;
use DTFormat\PhpDtformat\Segment;
use Carbon\Carbon;
use Throwable;

class OdbcTimestampParser implements ParserInterface
{
    public function parse(string $input): ?ParseResult
    {
        $trimmed = trim($input);
        
        // ODBC Timestamp literal: {ts 'YYYY-MM-DD HH:MM:SS'}
        // e.g., {ts '2026-07-01 09:13:48'}
        if (!preg_match('/^\{ts\s+\'(?P<year>\d{4})-(?P<month>\d{2})-(?P<day>\d{2})\s+(?P<hour>\d{2}):(?P<minute>\d{2}):(?P<second>\d{2})\'\}$/i', $trimmed, $matches, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        try {
            $carbon = Carbon::createFromFormat('Y-m-d H:i:s', $matches['year'][0] . '-' . $matches['month'][0] . '-' . $matches['day'][0] . ' ' . $matches['hour'][0] . ':' . $matches['minute'][0] . ':' . $matches['second'][0]);
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
        ]);

        return new ParseResult($carbon, 'odbc-timestamp', null, $segments);
    }
}
