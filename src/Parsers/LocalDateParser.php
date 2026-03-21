<?php

namespace DTFormat\PhpDtformat\Parsers;

use DTFormat\PhpDtformat\ParseResult;
use DTFormat\PhpDtformat\ParseSegmentBuilder;
use Carbon\Carbon;
use Throwable;

/**
 * Locale / human-readable date formats: d.m.Y, d/m/Y, m/d/Y, month name forms.
 * Does not match ISO-style Y-m-d (handled by ISO/SQL parsers).
 */
class LocalDateParser implements ParserInterface
{
    private const FORMATS = [
        // Dots/underscores (common in log/backup filenames)
        'Y.m.d.H.i',
        'Y.m.d.H.i.s',
        'Y_m_d_H_i',
        'Y_m_d_H_i_s',
        'Y.m.d H:i:s',
        'Y.m.d H:i',
        'Y.m.d',

        // Compact (no separators)
        'dmY',

        // Common European / US-style formats
        'd.m.Y H:i:s',
        'd.m.Y h:i:s A',
        'd.m.Y H:i',
        'd.m.Y h:i A',
        'd.m.Y',

        'd/m/Y H:i:s',
        'd/m/Y h:i:s A',
        'd/m/Y H:i',
        'd/m/Y h:i A',
        'd/m/Y',
    ];

    public function parse(string $input): ?ParseResult
    {
        $trimmed = trim($input);
        if ($trimmed === '' || preg_match('/^\d{4}-\d{2}-\d{2}/', $trimmed)) {
            return null;
        }

        foreach (self::FORMATS as $format) {
            try {
                // Carbon::createFromFormat keeps current clock time when the format has no time part.
                // Leading '!' resets time to 00:00:00 when time is omitted from the format.
                $carbon = Carbon::createFromFormat('!'.$format, $trimmed);
                if ($carbon !== false) {
                    // Compact formats: accept only if re-formatting equals input
                    // (otherwise PHP may normalize invalid parts, e.g. month 25).
                    if ($this->isCompactDateFormat($format) && $carbon->format($format) !== $trimmed) {
                        continue;
                    }
                    $mask = (str_contains($format, 'H') || str_contains($format, 'h') || str_contains($format, 'g')) ? 'datetime' : 'date';
                    $presentKeys = ['year', 'month', 'day'];
                    if ($mask === 'datetime') {
                        $presentKeys = array_merge($presentKeys, ['hour', 'minute']);
                        if (str_contains($format, 's')) {
                            $presentKeys[] = 'second';
                        }
                    }
                    $segments = ParseSegmentBuilder::localDate($trimmed, $format);

                    return new ParseResult($carbon, 'local-date', $mask, $segments, $presentKeys);
                }
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }

    private function isCompactDateFormat(string $format): bool
    {
        return ! preg_match('/[.\/\-\s]/', $format);
    }
}
