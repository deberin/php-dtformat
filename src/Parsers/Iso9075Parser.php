<?php

namespace DTFormat\PhpDtformat\Parsers;

use DTFormat\PhpDtformat\ParseResult;
use DTFormat\PhpDtformat\ParseSegmentBuilder;
use Carbon\Carbon;
use Throwable;

/**
 * ISO 9075 (SQL) date and datetime: YYYY-MM-DD [HH:MM:SS[.frac]]
 * Time separator is space, not T (distinguishes from ISO 8601).
 */
class Iso9075Parser implements ParserInterface
{
    private const DATE_ONLY = '/^\d{4}-\d{2}-\d{2}$/';

    private const DATETIME = '/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}(:\d{2})?(\.\d+)?$/';

    public function parse(string $input): ?ParseResult
    {
        $trimmed = trim($input);
        if ($trimmed === '') {
            return null;
        }

        $isDateOnly = preg_match(self::DATE_ONLY, $trimmed);
        $isDatetime = preg_match(self::DATETIME, $trimmed);
        if (! $isDateOnly && ! $isDatetime) {
            return null;
        }

        try {
            $carbon = Carbon::parse($trimmed);
        } catch (Throwable) {
            return null;
        }

        $mask = $isDateOnly ? 'date' : 'datetime';

        $segments = ParseSegmentBuilder::sql9075($trimmed);

        return new ParseResult($carbon, 'iso-9075', $mask, $segments);
    }
}
