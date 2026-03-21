<?php

namespace DTFormat\PhpDtformat\Parsers;

use DTFormat\PhpDtformat\ParseResult;
use DTFormat\PhpDtformat\ParseSegmentBuilder;
use Carbon\Carbon;
use Throwable;

class Rfc2822Parser implements ParserInterface
{
    /** RFC 2822: optional day-of-week ", " DD SP month-name SP year SP time SP zone */
    private const REGEX = '/^(?:Mon|Tue|Wed|Thu|Fri|Sat|Sun),?\s+\d{1,2}\s+(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+\d{4}\s+\d{2}:\d{2}(:\d{2})?\s+\S+$/i';

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

        $segments = ParseSegmentBuilder::rfc2822($trimmed);

        return new ParseResult($carbon, 'rfc-2822', null, $segments);
    }
}
