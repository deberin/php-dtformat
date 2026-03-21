<?php

namespace DTFormat\PhpDtformat\Parsers;

use DTFormat\PhpDtformat\ParseResult;
use DTFormat\PhpDtformat\ParseSegmentBuilder;
use Carbon\Carbon;
use Throwable;

/**
 * Chinese date: YYYY年M月D日 or YYYY年MM月DD日 (optional time  HH时MM分 or HH:MM).
 */
class ChineseDateParser implements ParserInterface
{
    private const REGEX = '/^(\d{4})年(\d{1,2})月(\d{1,2})日(?:\s+(\d{1,2})[时:](\d{2})(?:分)?)?$/u';

    public function parse(string $input): ?ParseResult
    {
        $trimmed = trim($input);
        if ($trimmed === '' || ! preg_match(self::REGEX, $trimmed, $m)) {
            return null;
        }

        $year = (int) $m[1];
        $month = (int) $m[2];
        $day = (int) $m[3];
        $hour = isset($m[4]) ? (int) $m[4] : 0;
        $minute = isset($m[5]) ? (int) $m[5] : 0;

        if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
            return null;
        }
        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
            return null;
        }

        try {
            $carbon = Carbon::create($year, $month, $day, $hour, $minute, 0);
        } catch (Throwable) {
            return null;
        }

        $mask = isset($m[4]) ? 'datetime' : 'date';
        $segments = ParseSegmentBuilder::chinese($trimmed);

        return new ParseResult($carbon, 'chinese-date', $mask, $segments);
    }
}
