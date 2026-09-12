<?php

namespace DTFormat\PhpDtformat\Parsers;

use DTFormat\PhpDtformat\ParseResult;
use DTFormat\PhpDtformat\Segment;
use Carbon\Carbon;
use Throwable;

class OracleTimestampParser implements ParserInterface
{
    public function parse(string $input): ?ParseResult
    {
        $trimmed = trim($input);
        
        // Oracle timestamp format: DD-MON-YY HH.MI.SS.FF PM or DD-MON-YY HH.MI.SS PM
        // e.g., 14-FEB-17 10.31.17.447000000 PM or 03-SEP-23 12.03.00 PM
        if (!preg_match('/^(\d{2}-[a-z]{3}-\d{2}\s\d{2}\.\d{2}\.\d{2})(?:\.(\d+))?\s(AM|PM)$/i', $trimmed, $matches)) {
            return null;
        }

        try {
            $datePart = $matches[1];
            $microPart = $matches[2] ?? '0';
            $amPmPart = strtoupper($matches[3]);

            $normalizedString = str_replace('.', ':', $datePart) . ' ' . $amPmPart;
            $carbon = Carbon::createFromFormat('d-M-y h:i:s A', $normalizedString);
            
            if ($microPart !== '0') {
                // Pad or truncate to 6 digits for microseconds
                $microPart = str_pad(substr($microPart, 0, 6), 6, '0');
                $carbon->setMicroseconds((int)$microPart);
            }
        } catch (Throwable) {
            return null;
        }

        $segments = [new Segment('oracle_timestamp', $trimmed, 0, strlen($trimmed))];

        return new ParseResult($carbon, 'oracle-timestamp', null, $segments);
    }
}
