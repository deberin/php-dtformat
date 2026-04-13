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
        'Y-m-d-H.i.s.u',
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

        // Slash Y/m/d variants from logs/user input
        'Y/m/d H:i:s O',
        'Y/m/d H:i:s P',
        'Y/m/d H:i:s',
        'Y/m/d H:i',
        'Y/m/d',
        'Y/n/j H:i:s O',
        'Y/n/j H:i:s P',
        'Y/n/j H:i:s',
        'Y/n/j H:i',
        'Y/n/j',

        // Colon date variants: YYYY:MM:DD ...
        'Y:m:d H:i:s',
        'Y:m:d H:i:s P',
        'Y:m:d H:i:s.u',
        'Y:m:d H:i:s.uP',
        'Y:m:d',

        // Compact timestamp-like values: yyyyMMddHHmmss.fraction[+/-offset]
        'YmdHis.u',
        'YmdHis.uO',
    ];

    public function parse(string $input): ?ParseResult
    {
        $trimmed = trim($input);
        $isIsoLikeYmdPrefix = preg_match('/^\d{4}-\d{2}-\d{2}/', $trimmed) === 1;
        $isDashDotLogStyle = preg_match('/^\d{4}-\d{2}-\d{2}-\d{2}\.\d{2}\.\d{2}\.\d+$/', $trimmed) === 1;
        if ($trimmed === '' || ($isIsoLikeYmdPrefix && ! $isDashDotLogStyle)) {
            return null;
        }

        $candidates = [$trimmed];
        $normalizedCompactOffset = $this->normalizeCompactFractionalOffset($trimmed);
        if ($normalizedCompactOffset !== $trimmed) {
            $candidates = [$normalizedCompactOffset];
        }

        foreach ($candidates as $candidate) {
            foreach (self::FORMATS as $format) {
                try {
                    // Carbon::createFromFormat keeps current clock time when the format has no time part.
                    // Leading '!' resets time to 00:00:00 when time is omitted from the format.
                    $carbon = Carbon::createFromFormat('!'.$format, $candidate);
                    if ($carbon !== false) {
                        // Compact formats: accept only if re-formatting equals input
                        // (otherwise PHP may normalize invalid parts, e.g. month 25).
                        if ($this->isCompactDateFormat($format) && $carbon->format($format) !== $candidate) {
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
        }

        return null;
    }

    private function isCompactDateFormat(string $format): bool
    {
        return ! preg_match('/[.\/\-\s]/', $format);
    }

    private function normalizeCompactFractionalOffset(string $input): string
    {
        if (! preg_match('/^\d{14}\.\d+[+-]\d{3}$/', $input)) {
            return $input;
        }

        return preg_replace('/([+-]\d{2})\d$/', '${1}00', $input) ?? $input;
    }
}
