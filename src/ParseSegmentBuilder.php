<?php

namespace DTFormat\PhpDtformat;

/**
 * Builds highlight segments for various formats (ISO 8601 uses dedicated logic in Iso8601Parser).
 */
final class ParseSegmentBuilder
{
    private const MONTHS_F = 'January|February|March|April|May|June|July|August|September|October|November|December';

    private const MONTHS_M = 'Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec';

    private const WEEKDAYS_F = 'Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday';

    private const WEEKDAYS_M = 'Mon|Tue|Wed|Thu|Fri|Sat|Sun';

    /** RFC 3339 full-date T full-time */
    private const RFC3339 = '/^(?P<year>\d{4})-(?P<month>\d{2})-(?P<day>\d{2})(?P<sep>[Tt])(?P<hour>\d{2}):(?P<minute>\d{2})(?P<second>:\d{2})?(?P<frac>\.\d+)?(?P<offset>Z|[+-]\d{2}:?\d{2})$/';

    private const SQL_DATE = '/^(?P<year>\d{4})-(?P<month>\d{2})-(?P<day>\d{2})$/';

    private const SQL_DATETIME = '/^(?P<year>\d{4})-(?P<month>\d{2})-(?P<day>\d{2})\s+(?P<hour>\d{2}):(?P<minute>\d{2})(?P<second>:\d{2})?(?P<frac>\.\d+)?$/';

    private const RFC2822 = '/^(?P<wday>Mon|Tue|Wed|Thu|Fri|Sat|Sun),?\s+(?P<day>\d{1,2})\s+(?P<mon>Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+(?P<year>\d{4})\s+(?P<hour>\d{2}):(?P<minute>\d{2})(?P<second>:\d{2})?\s+(?P<zone>\S+)$/i';

    private const CHINESE = '/^(?P<year>\d{4})年(?P<month>\d{1,2})月(?P<day>\d{1,2})日(?:\s+(?P<hour>\d{1,2})[时:](?P<minute>\d{2})(?:分)?)?$/u';

    /**
     * @param  array<string, array{0: string, 1: int}>  $m
     * @return array<int, Segment>
     */
    public static function fromNamedMatch(array $m, array $map): array
    {
        $out = [];
        foreach ($map as $name => $key) {
            if (! isset($m[$name]) || ! is_array($m[$name]) || $m[$name][1] < 0 || $m[$name][0] === '') {
                continue;
            }
            $val = $m[$name][0];
            $start = $m[$name][1];
            if ($name === 'second' && str_starts_with($val, ':')) {
                $val = substr($val, 1);
                $start++;
            }
            $out[] = new Segment($key, $val, $start, $start + strlen($val));
        }

        return $out;
    }

    /** @return array<int, Segment> */
    public static function rfc3339(string $input): array
    {
        if (! preg_match(self::RFC3339, $input, $m, PREG_OFFSET_CAPTURE)) {
            return [];
        }
        $segments = [];
        $order = ['year', 'month', 'day', 'sep', 'hour', 'minute', 'second', 'frac', 'offset'];
        $keys = [
            'year' => 'year',
            'month' => 'month',
            'day' => 'day',
            'sep' => 'separator',
            'hour' => 'hour',
            'minute' => 'minute',
            'second' => 'second',
            'frac' => 'millisecond',
            'offset' => 'offset',
        ];
        foreach ($order as $name) {
            if (! isset($m[$name]) || $m[$name][1] < 0 || $m[$name][0] === '') {
                continue;
            }
            $val = $m[$name][0];
            $start = $m[$name][1];
            if ($name === 'minute' || $name === 'second') {
                $val = ltrim($val, ':');
                $start = $m[$name][1] + (strlen($m[$name][0]) - strlen($val));
            }
            $key = $keys[$name];
            $segments[] = new Segment($key, $val, $start, $start + strlen($val));
        }

        return $segments;
    }

    /** @return array<int, Segment> */
    public static function sql9075(string $input): array
    {
        if (preg_match(self::SQL_DATE, $input, $m, PREG_OFFSET_CAPTURE)) {
            return self::fromNamedMatch($m, [
                'year' => 'year',
                'month' => 'month',
                'day' => 'day',
            ]);
        }
        if (preg_match(self::SQL_DATETIME, $input, $m, PREG_OFFSET_CAPTURE)) {
            $segments = self::fromNamedMatch($m, [
                'year' => 'year',
                'month' => 'month',
                'day' => 'day',
                'hour' => 'hour',
                'minute' => 'minute',
                'second' => 'second',
            ]);
            if (isset($m['frac']) && $m['frac'][0] !== '' && $m['frac'][1] >= 0) {
                $segments[] = new Segment('millisecond', $m['frac'][0], $m['frac'][1], $m['frac'][1] + strlen($m['frac'][0]));
            }

            return $segments;
        }

        return [];
    }

    /** @return array<int, Segment> */
    public static function rfc2822(string $input): array
    {
        if (! preg_match(self::RFC2822, $input, $m, PREG_OFFSET_CAPTURE)) {
            return [];
        }
        $segments = [];
        if ($m['wday'][0] !== '' && $m['wday'][1] >= 0) {
            $segments[] = new Segment('weekday_abbr', $m['wday'][0], $m['wday'][1], $m['wday'][1] + strlen($m['wday'][0]));
        }
        if ($m['day'][0] !== '') {
            $segments[] = new Segment('day', $m['day'][0], $m['day'][1], $m['day'][1] + strlen($m['day'][0]));
        }
        if ($m['mon'][0] !== '') {
            $segments[] = new Segment('month_name', $m['mon'][0], $m['mon'][1], $m['mon'][1] + strlen($m['mon'][0]));
        }
        if ($m['year'][0] !== '') {
            $segments[] = new Segment('year', $m['year'][0], $m['year'][1], $m['year'][1] + strlen($m['year'][0]));
        }
        if ($m['hour'][0] !== '') {
            $segments[] = new Segment('hour', $m['hour'][0], $m['hour'][1], $m['hour'][1] + strlen($m['hour'][0]));
        }
        if ($m['minute'][0] !== '') {
            $segments[] = new Segment('minute', $m['minute'][0], $m['minute'][1], $m['minute'][1] + strlen($m['minute'][0]));
        }
        if (isset($m['second']) && $m['second'][0] !== '' && $m['second'][1] >= 0) {
            $v = ltrim($m['second'][0], ':');
            $st = $m['second'][1] + 1;
            $segments[] = new Segment('second', $v, $st, $st + strlen($v));
        }
        if ($m['zone'][0] !== '') {
            $segments[] = new Segment('timezone_abbr', $m['zone'][0], $m['zone'][1], $m['zone'][1] + strlen($m['zone'][0]));
        }

        return $segments;
    }

    /** @return array<int, Segment> */
    public static function chinese(string $input): array
    {
        if (! preg_match(self::CHINESE, $input, $m, PREG_OFFSET_CAPTURE)) {
            return [];
        }
        $segments = [];
        foreach (['year' => 'year', 'month' => 'month', 'day' => 'day'] as $g => $key) {
            if ($m[$g][0] !== '' && $m[$g][1] >= 0) {
                $segments[] = new Segment($key, $m[$g][0], $m[$g][1], $m[$g][1] + strlen($m[$g][0]));
            }
        }
        if (isset($m['hour']) && $m['hour'][0] !== '' && $m['hour'][1] >= 0) {
            $segments[] = new Segment('hour', $m['hour'][0], $m['hour'][1], $m['hour'][1] + strlen($m['hour'][0]));
        }
        if (isset($m['minute']) && $m['minute'][0] !== '' && $m['minute'][1] >= 0) {
            $segments[] = new Segment('minute', $m['minute'][0], $m['minute'][1], $m['minute'][1] + strlen($m['minute'][0]));
        }

        return $segments;
    }

    /** @return array<int, Segment> */
    public static function localDate(string $input, string $format): array
    {
        $format = ltrim($format, '!');
        $segments = [];
        $pos = 0;
        $flen = strlen($format);
        $ilen = strlen($input);
        for ($i = 0; $i < $flen; $i++) {
            $fc = $format[$i];
            if ($fc === '\\') {
                $i++;

                continue;
            }
            if ($fc === ' ') {
                while ($pos < $ilen && ctype_space($input[$pos])) {
                    $pos++;
                }

                continue;
            }
            if (in_array($fc, ['.', '/', '-', '_', ':', ','], true)) {
                if ($pos < $ilen && $input[$pos] === $fc) {
                    $pos++;
                }

                continue;
            }

            $rest = substr($input, $pos);
            $append = function (string $key, string $val) use (&$segments, &$pos): void {
                if ($val === '') {
                    return;
                }
                $start = $pos;
                $len = strlen($val);
                $segments[] = new Segment($key, $val, $start, $start + $len);
                $pos += $len;
            };

            switch ($fc) {
                case 'Y':
                    if (preg_match('/^\d{4}/', $rest, $mm)) {
                        $append('year', $mm[0]);
                    }
                    break;
                case 'y':
                    if (preg_match('/^\d{2}/', $rest, $mm)) {
                        $append('year', $mm[0]);
                    }
                    break;
                case 'm':
                    if (preg_match('/^\d{2}/', $rest, $mm)) {
                        $append('month', $mm[0]);
                    }
                    break;
                case 'n':
                    if (preg_match('/^\d{1,2}/', $rest, $mm)) {
                        $append('month', $mm[0]);
                    }
                    break;
                case 'd':
                    if (preg_match('/^\d{2}/', $rest, $mm)) {
                        $append('day', $mm[0]);
                    }
                    break;
                case 'j':
                    if (preg_match('/^\d{1,2}/', $rest, $mm)) {
                        $append('day', $mm[0]);
                    }
                    break;
                case 'H':
                case 'h':
                    if (preg_match('/^\d{2}/', $rest, $mm)) {
                        $append('hour', $mm[0]);
                    }
                    break;
                case 'G':
                case 'g':
                    if (preg_match('/^\d{1,2}/', $rest, $mm)) {
                        $append('hour', $mm[0]);
                    }
                    break;
                case 'i':
                    if (preg_match('/^\d{2}/', $rest, $mm)) {
                        $append('minute', $mm[0]);
                    }
                    break;
                case 's':
                    if (preg_match('/^\d{2}/', $rest, $mm)) {
                        $append('second', $mm[0]);
                    }
                    break;
                case 'F':
                    if (preg_match('/^('.self::MONTHS_F.')/i', $rest, $mm)) {
                        $append('month_name', $mm[0]);
                    }
                    break;
                case 'M':
                    if (preg_match('/^('.self::MONTHS_M.')/i', $rest, $mm)) {
                        $append('month_name', $mm[0]);
                    }
                    break;
                case 'A':
                case 'a':
                    if (preg_match('/^(AM|PM|am|pm)/', $rest, $mm)) {
                        $append('meridiem', $mm[0]);
                    }
                    break;
                default:
                    if ($pos < $ilen && $input[$pos] === $fc) {
                        $pos++;
                    }
                    break;
            }
        }

        return $segments;
    }

    /** @return array<int, Segment> */
    public static function textDate(string $input): array
    {
        $raw = $input;
        $segments = [];

        // Weekday (full/abbr)
        if (preg_match('/\b('.self::WEEKDAYS_F.'|'.self::WEEKDAYS_M.')\b/i', $raw, $m, PREG_OFFSET_CAPTURE)) {
            $segments[] = new Segment('weekday_abbr', $m[0][0], $m[0][1], $m[0][1] + strlen($m[0][0]));
        }

        // Month name (full/abbr)
        if (preg_match('/\b('.self::MONTHS_F.'|'.self::MONTHS_M.')\b/i', $raw, $m, PREG_OFFSET_CAPTURE)) {
            $segments[] = new Segment('month_name', $m[0][0], $m[0][1], $m[0][1] + strlen($m[0][0]));
        }

        // Year
        if (preg_match('/\b(\d{4})\b/', $raw, $m, PREG_OFFSET_CAPTURE)) {
            $segments[] = new Segment('year', $m[1][0], $m[1][1], $m[1][1] + strlen($m[1][0]));
        }

        // Day (with optional ordinal suffix)
        if (preg_match('/\b(\d{1,2})(?:st|nd|rd|th)?\b/i', $raw, $m, PREG_OFFSET_CAPTURE)) {
            $segments[] = new Segment('day', $m[1][0], $m[1][1], $m[1][1] + strlen($m[1][0]));
        }

        // Time + meridiem
        if (preg_match('/\b(\d{1,2}):(\d{2})(?::(\d{2}))?\b/', $raw, $tm, PREG_OFFSET_CAPTURE)) {
            $segments[] = new Segment('hour', $tm[1][0], $tm[1][1], $tm[1][1] + strlen($tm[1][0]));
            $segments[] = new Segment('minute', $tm[2][0], $tm[2][1], $tm[2][1] + strlen($tm[2][0]));
            if (isset($tm[3]) && is_array($tm[3]) && ($tm[3][1] ?? -1) >= 0 && ($tm[3][0] ?? '') !== '') {
                $segments[] = new Segment('second', $tm[3][0], $tm[3][1], $tm[3][1] + strlen($tm[3][0]));
            }
        }

        if (preg_match('/\b(am|pm)\b/i', $raw, $m, PREG_OFFSET_CAPTURE)) {
            $segments[] = new Segment('meridiem', $m[1][0], $m[1][1], $m[1][1] + strlen($m[1][0]));
        }

        // Sort and remove overlaps (keep first by position)
        usort($segments, fn (Segment $a, Segment $b) => $a->start <=> $b->start);
        $out = [];
        $cursor = -1;
        foreach ($segments as $s) {
            if ($s->start < $cursor) {
                continue;
            }
            $out[] = $s;
            $cursor = $s->end;
        }

        return $out;
    }

    /** @return array<int, Segment> */
    public static function pdfDate(string $rawInput): array
    {
        $raw = trim($rawInput);
        $segments = [];
        $base = 0;
        if (str_starts_with($raw, 'D:')) {
            $segments[] = new Segment('pdf_prefix', 'D:', 0, 2);
            $body = substr($raw, 2);
            $base = 2;
        } else {
            $body = $raw;
        }
        if (! preg_match('/^(\d{14})(.*)$/s', $body, $m, PREG_OFFSET_CAPTURE)) {
            return $segments;
        }
        $dt = $m[1][0];
        $dtStart = $base + $m[1][1];
        $segments[] = new Segment('year', substr($dt, 0, 4), $dtStart, $dtStart + 4);
        $segments[] = new Segment('month', substr($dt, 4, 2), $dtStart + 4, $dtStart + 6);
        $segments[] = new Segment('day', substr($dt, 6, 2), $dtStart + 6, $dtStart + 8);
        $segments[] = new Segment('hour', substr($dt, 8, 2), $dtStart + 8, $dtStart + 10);
        $segments[] = new Segment('minute', substr($dt, 10, 2), $dtStart + 10, $dtStart + 12);
        $segments[] = new Segment('second', substr($dt, 12, 2), $dtStart + 12, $dtStart + 14);
        $off = $m[2][0] ?? '';
        if ($off !== '' && ($m[2][1] ?? -1) >= 0) {
            $segments[] = new Segment('offset', $off, $base + $m[2][1], $base + $m[2][1] + strlen($off));
        }

        return $segments;
    }
}
