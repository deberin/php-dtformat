<?php

namespace DTFormat\PhpDtformat\Parsers;

use DTFormat\PhpDtformat\ParseResult;
use DTFormat\PhpDtformat\Segment;
use Carbon\Carbon;
use Throwable;

class UnixTimestampParser implements ParserInterface
{
    public function parse(string $input): ?ParseResult
    {
        $trimmed = trim($input);
        if ($trimmed === '' || ! ctype_digit($trimmed)) {
            return null;
        }

        $len = strlen($trimmed);
        $mask = null;
        $seconds = 0;

        if ($len <= 10) {
            $seconds = (int) $trimmed;
            $milliseconds = 0;
            $mask = 'seconds';
        } elseif ($len <= 13) {
            $value = (int) $trimmed;
            $seconds = (int) floor($value / 1000);
            $milliseconds = $value % 1000;
            $mask = 'milliseconds';
        } else {
            return null;
        }

        try {
            $carbon = Carbon::createFromTimestamp($seconds);
            if ($milliseconds > 0) {
                $carbon->setMilliseconds($milliseconds);
            }
        } catch (Throwable) {
            return null;
        }

        if ($len <= 10) {
            $segments = [new Segment('unix_value', $trimmed, 0, $len)];
        } else {
            $secLen = $len - 3;
            $segments = [
                new Segment('unix_seconds_part', substr($trimmed, 0, $secLen), 0, $secLen),
                new Segment('unix_milliseconds_part', substr($trimmed, $secLen), $secLen, $len),
            ];
        }

        return new ParseResult($carbon, 'unix-timestamp', $mask, $segments);
    }
}
