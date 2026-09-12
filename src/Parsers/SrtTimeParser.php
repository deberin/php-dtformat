<?php

namespace DTFormat\PhpDtformat\Parsers;

use DTFormat\PhpDtformat\ParseResult;
use DTFormat\PhpDtformat\Segment;
use Carbon\Carbon;
use Throwable;

class SrtTimeParser implements ParserInterface
{
    public function parse(string $input): ?ParseResult
    {
        $trimmed = trim($input);
        
        // SRT time format: HH:MM:SS,mmm
        // e.g., 01:00:15,920
        if (!preg_match('/^(?P<hour>\d{2}):(?P<minute>\d{2}):(?P<second>\d{2}),(?P<millisecond>\d{3})$/', $trimmed, $matches, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        try {
            $timePart = $matches['hour'][0] . ':' . $matches['minute'][0] . ':' . $matches['second'][0];
            $milliPart = $matches['millisecond'][0];
            
            // SRT only has time, we set date to today for consistency, or leave it as today which is Carbon's default when parsing just time.
            $carbon = Carbon::createFromFormat('H:i:s', $timePart);
            $carbon->setMilliseconds((int)$milliPart);
        } catch (Throwable) {
            return null;
        }

        $segments = \DTFormat\PhpDtformat\ParseSegmentBuilder::fromNamedMatch($matches, [
            'hour' => 'hour',
            'minute' => 'minute',
            'second' => 'second',
            'millisecond' => 'millisecond',
        ]);

        return new ParseResult($carbon, 'srt-time', null, $segments, ['hour', 'minute', 'second']);
    }
}
