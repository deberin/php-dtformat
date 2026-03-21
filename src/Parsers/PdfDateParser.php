<?php

namespace DTFormat\PhpDtformat\Parsers;

use DTFormat\PhpDtformat\ParseResult;
use DTFormat\PhpDtformat\ParseSegmentBuilder;
use Carbon\Carbon;
use Throwable;

class PdfDateParser implements ParserInterface
{
    /**
     * PDF Date format (PDF 32000-1:2008):
     * D:YYYYMMDDHHmmSSOHH'mm'
     * e.g. 20180921141013-04'00' — optional D: prefix; optional offset minutes/seconds.
     */
    public function parse(string $input): ?ParseResult
    {
        $trimmed = trim($input);
        if ($trimmed === '') {
            return null;
        }

        $segments = ParseSegmentBuilder::pdfDate($trimmed);

        // Strip optional D: prefix
        if (str_starts_with($trimmed, 'D:')) {
            $trimmed = substr($trimmed, 2);
        }

        // At least 14 digits: YYYYMMDDHHmmSS
        // Offset may be +HH'mm', -HH'mm', Z, or omitted
        if (! preg_match('/^(\d{14})(.*)$/', $trimmed, $matches)) {
            return null;
        }

        $datetime = $matches[1]; // YYYYMMDDHHmmSS
        $offsetPart = $matches[2]; // May be empty, 'Z', '+HH\'mm\'', '-HH\'mm\'', '+HH', '-HH'

        // Normalize datetime for Carbon: YYYYMMDDHHmmSS -> YYYY-MM-DD HH:mm:ss
        $formattedDatetime = sprintf(
            '%s-%s-%s %s:%s:%s',
            substr($datetime, 0, 4),
            substr($datetime, 4, 2),
            substr($datetime, 6, 2),
            substr($datetime, 8, 2),
            substr($datetime, 10, 2),
            substr($datetime, 12, 2)
        );

        $formattedOffset = '';
        if ($offsetPart !== '') {
            if ($offsetPart === 'Z' || $offsetPart === 'z') {
                $formattedOffset = 'Z';
            } else {
                // Match +HH'mm', -HH'mm', +HH'mm, -HH'mm, +HH, -HH
                if (preg_match('/^([+-])(\d{2})\'?(\d{2})?\'?$/', $offsetPart, $offsetMatches)) {
                    $sign = $offsetMatches[1];
                    $hours = $offsetMatches[2];
                    $minutes = $offsetMatches[3] ?? '00';
                    $formattedOffset = "{$sign}{$hours}:{$minutes}";
                } else {
                    return null; // Unknown offset format
                }
            }
        }

        try {
            $carbon = Carbon::parse($formattedDatetime.$formattedOffset);
        } catch (Throwable) {
            return null;
        }

        $mask = $formattedOffset === '' ? 'local' : 'utc_or_offset';

        return new ParseResult($carbon, 'pdf-date', $mask, $segments, ['year', 'month', 'day', 'hour', 'minute', 'second']);
    }
}
