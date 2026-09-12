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
        if (!preg_match('/^(?P<day>\d{2})-(?P<month>[a-z]{3})-(?P<year>\d{2})\s(?P<hour>\d{2})\.(?P<minute>\d{2})\.(?P<second>\d{2})(?:\.(?P<millisecond>\d+))?\s(?P<ampm>AM|PM)$/i', $trimmed, $matches, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        try {
            $datePart = $matches['day'][0] . '-' . $matches['month'][0] . '-' . $matches['year'][0];
            $timePart = $matches['hour'][0] . ':' . $matches['minute'][0] . ':' . $matches['second'][0];
            $microPart = isset($matches['millisecond']) && $matches['millisecond'][1] >= 0 ? $matches['millisecond'][0] : '0';
            $amPmPart = strtoupper($matches['ampm'][0]);

            $normalizedString = $datePart . ' ' . $timePart . ' ' . $amPmPart;
            $carbon = Carbon::createFromFormat('d-M-y h:i:s A', $normalizedString);
            
            if ($microPart !== '0') {
                // Pad or truncate to 6 digits for microseconds
                $microPart = str_pad(substr($microPart, 0, 6), 6, '0');
                $carbon->setMicroseconds((int)$microPart);
            }
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
            'ampm' => 'ampm',
        ]);

        return new ParseResult($carbon, 'oracle-timestamp', null, $segments);
    }
}
