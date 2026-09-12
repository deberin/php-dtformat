<?php

namespace DTFormat\PhpDtformat\Parsers;

use DTFormat\PhpDtformat\ParseResult;
use DTFormat\PhpDtformat\Segment;
use Carbon\Carbon;
use Throwable;

class JsDateStringParser implements ParserInterface
{
    public function parse(string $input): ?ParseResult
    {
        $trimmed = trim($input, " \t\n\r\0\x0B\"'");
        
        $matches = [];
        $matched = false;

        // 1. Mon Dec 01 2026 17:07:18 GMT-0700
        if (!$matched && preg_match('/^(?P<weekday>[a-z]{3})\s+(?P<month>[a-z]{3})\s+(?P<day>\d{2})\s+(?P<year>\d{4})\s+(?P<hour>\d{2}):(?P<minute>\d{2}):(?P<second>\d{2})(?:\s+GMT(?P<offset>[+-]\d{4}))?/i', $trimmed, $matches, PREG_OFFSET_CAPTURE)) {
            $matched = true;
        }
        
        // 2. Sun Aug 09 14:38:59 EDT 2026
        if (!$matched && preg_match('/^(?P<weekday>[a-z]{3})\s+(?P<month>[a-z]{3})\s+(?P<day>\d{2})\s+(?P<hour>\d{2}):(?P<minute>\d{2}):(?P<second>\d{2})\s+(?P<zone>[a-z]{3,4})\s+(?P<year>\d{4})/i', $trimmed, $matches, PREG_OFFSET_CAPTURE)) {
            $matched = true;
        }
        
        if (!$matched) {
            return null;
        }

        try {
            $cleanString = preg_replace('/\s+\(.*\)$/', '', $trimmed);
            $carbon = Carbon::parse($cleanString);
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
            'offset' => 'offset',
            'zone' => 'timezone_abbr',
            'weekday' => 'weekday_abbr',
        ]);

        return new ParseResult($carbon, 'js-date-string', null, $segments);
    }
}
