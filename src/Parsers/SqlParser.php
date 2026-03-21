<?php

namespace DTFormat\PhpDtformat\Parsers;

use DTFormat\PhpDtformat\ParseResult;
use DTFormat\PhpDtformat\ParseSegmentBuilder;
use Carbon\Carbon;
use Throwable;

/**
 * SQL DATE / DATETIME: YYYY-MM-DD [HH:MM:SS[.frac]]
 * Same as ISO 9075; separate format slug for SEO/content.
 */
class SqlParser implements ParserInterface
{
    private const DATE_ONLY = '/^\d{4}-\d{2}-\d{2}$/';

    private const DATETIME = '/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}(:\d{2})?(\.\d+)?$/';

    public function parse(string $input): ?ParseResult
    {
        $trimmed = trim($input);
        if ($trimmed === '' || (! preg_match(self::DATE_ONLY, $trimmed) && ! preg_match(self::DATETIME, $trimmed))) {
            return null;
        }

        try {
            $carbon = Carbon::parse($trimmed);
        } catch (Throwable) {
            return null;
        }

        $mask = preg_match(self::DATE_ONLY, $trimmed) ? 'date' : 'datetime';

        $segments = ParseSegmentBuilder::sql9075($trimmed);

        return new ParseResult($carbon, 'sql', $mask, $segments);
    }
}
