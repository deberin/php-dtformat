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
        if (!preg_match('/^(\d{2}:\d{2}:\d{2}),(\d{3})$/', $trimmed, $matches)) {
            return null;
        }

        try {
            $timePart = $matches[1];
            $milliPart = $matches[2];
            
            // SRT only has time, we set date to today for consistency, or leave it as today which is Carbon's default when parsing just time.
            $carbon = Carbon::createFromFormat('H:i:s', $timePart);
            $carbon->setMilliseconds((int)$milliPart);
        } catch (Throwable) {
            return null;
        }

        $segments = [new Segment('srt_time', $trimmed, 0, strlen($trimmed))];

        return new ParseResult($carbon, 'srt-time', null, $segments, ['hour', 'minute', 'second']);
    }
}
