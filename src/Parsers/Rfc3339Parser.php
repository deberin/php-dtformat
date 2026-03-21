<?php

namespace DTFormat\PhpDtformat\Parsers;

use DTFormat\PhpDtformat\ParseResult;
use DTFormat\PhpDtformat\ParseSegmentBuilder;
use Carbon\Carbon;
use Throwable;

class Rfc3339Parser implements ParserInterface
{
    /** RFC 3339: full-date "T" full-time time-offset (Z or ±HH:MM) */
    private const REGEX = '/^\d{4}-\d{2}-\d{2}[Tt]\d{2}:\d{2}(:\d{2})?(\.\d+)?([Zz]|[+-]\d{2}:?\d{2})$/';

    public function parse(string $input): ?ParseResult
    {
        $trimmed = trim($input);
        if ($trimmed === '' || ! preg_match(self::REGEX, $trimmed)) {
            return null;
        }

        try {
            $carbon = Carbon::parse($trimmed);
        } catch (Throwable) {
            return null;
        }

        $mask = (str_ends_with($trimmed, 'Z') || str_ends_with($trimmed, 'z'))
            ? 'utc'
            : 'offset';

        $segments = ParseSegmentBuilder::rfc3339($trimmed);

        return new ParseResult($carbon, 'rfc-3339', $mask, $segments);
    }
}
