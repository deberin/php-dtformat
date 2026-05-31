<?php

namespace DTFormat\PhpDtformat\Parsers;

use DTFormat\PhpDtformat\ParseResult;
use DTFormat\PhpDtformat\ParseSegmentBuilder;
use Carbon\Carbon;
use Throwable;

/**
 * US-style numeric dates where month comes first: m/d/Y, m-d-Y, and compact mdY.
 */
class UsDateParser implements ParserInterface
{
    private const FORMATS = [
        // Compact (no separators)
        'mdY',

        // Slash-separated
        'm/d/Y H:i:s.u',
        'm/d/Y H:i:s.v',
        'm/d/Y H:i:s',
        'm/d/Y h:i:s A',
        'm/d/Y H:i',
        'm/d/Y h:i A',
        'm/d/Y',

        // Dash-separated
        'm-d-Y H:i:s',
        'm-d-Y h:i:s A',
        'm-d-Y H:i',
        'm-d-Y h:i A',
        'm-d-Y',
    ];

    public function parse(string $input): ?ParseResult
    {
        $trimmed = trim($input);
        if ($trimmed === '' || preg_match('/^\d{4}-\d{2}-\d{2}/', $trimmed)) {
            return null;
        }

        foreach (self::FORMATS as $format) {
            try {
                $carbon = Carbon::createFromFormat('!' . $format, $trimmed);
                if ($carbon !== false) {
                    $errors = Carbon::getLastErrors();
                    if ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
                        continue;
                    }
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

                    return new ParseResult($carbon, 'us-date', $mask, $segments, $presentKeys);
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
