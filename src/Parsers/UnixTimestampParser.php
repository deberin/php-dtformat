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
        if ($trimmed === '' || ! preg_match('/^\d+(?:\.\d+)?$/', $trimmed)) {
            return null;
        }

        $mask = null;
        $seconds = 0;
        $milliseconds = 0;

        if (str_contains($trimmed, '.')) {
            [$secStr, $fracStr] = explode('.', $trimmed);
            if (strlen($secStr) > 11) {
                return null;
            }
            $seconds = (int) $secStr;
            $msStr = substr($fracStr, 0, 3);
            $milliseconds = (int) str_pad($msStr, 3, '0', STR_PAD_RIGHT);
            $mask = 'fractional';
            $segments = [
                new Segment('unix_seconds_part', $secStr, 0, strlen($secStr)),
                new Segment('unix_milliseconds_part', $fracStr, strlen($secStr) + 1, strlen($trimmed)),
            ];
        } else {
            $len = strlen($trimmed);
            if ($len <= 10) {
                $seconds = (int) $trimmed;
                $milliseconds = 0;
                $mask = 'seconds';
                $segments = [new Segment('unix_value', $trimmed, 0, $len)];
            } elseif ($len <= 13) {
                $value = (int) $trimmed;
                $seconds = (int) floor($value / 1000);
                $milliseconds = $value % 1000;
                $mask = 'milliseconds';
                $secLen = $len - 3;
                $segments = [
                    new Segment('unix_seconds_part', substr($trimmed, 0, $secLen), 0, $secLen),
                    new Segment('unix_milliseconds_part', substr($trimmed, $secLen), $secLen, $len),
                ];
            } else {
                return null;
            }
        }

        try {
            $carbon = Carbon::createFromTimestamp($seconds);
            if ($milliseconds > 0) {
                $carbon->setMilliseconds($milliseconds);
            }
        } catch (Throwable) {
            return null;
        }

        return new ParseResult($carbon, 'unix-timestamp', $mask, $segments);
    }
}
