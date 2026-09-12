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
        if (!preg_match('/^\{ts\s+\'(\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2})\'\}$/i', $trimmed, $matches)) {
            return null;
        }

        try {
            $carbon = Carbon::createFromFormat('Y-m-d H:i:s', $matches[1]);
        } catch (Throwable) {
            return null;
        }

        $segments = [new Segment('odbc_timestamp', $trimmed, 0, strlen($trimmed))];

        return new ParseResult($carbon, 'odbc-timestamp', null, $segments);
    }
}
