<?php

namespace DTFormat\PhpDtformat\Parsers;

use DTFormat\PhpDtformat\ParseResult;
use DTFormat\PhpDtformat\Segment;
use Carbon\Carbon;
use Throwable;

class Iso8601Parser implements ParserInterface
{
    /** Common wrappers/noise around copied ISO strings in logs/text. */
    private const EDGE_NOISE_CHARS = " \t\n\r\0\x0B'\"[]()";

    /** ISO 8601 date-time: year, optional month/day, optional time and offset. */
    private const REGEX_DATETIME = '/^(?P<year>\d{4})(?P<m1>-?)(?P<month>\d{2})?(?P<m2>-?)(?P<day>\d{2})?(?P<time_sep>[Tt\s])?(?P<hour>\d{1,2})?(?P<minute>:\d{2})?(?P<second>:\d{2})?(?P<frac>\.\d{1,9})?(?P<offset>[Zz]|[+-]\d{2}(?::?\d{2})?)?$/';

    /** Time only: optional T, hours:minutes:seconds. */
    private const REGEX_TIME_ONLY = '/^(?P<time_sep>[Tt])?(?P<hour>\d{1,2})(?P<minute>:\d{2})(?P<second>:\d{2})?(?P<frac>\.\d{1,9})?(?P<offset>[Zz]|[+-]\d{2}(?::?\d{2})?)?$/';

    /** Compact ISO: YYYYMMDDThhmmss with optional Z/offset (no colons in the time part). */
    private const REGEX_COMPACT_DATETIME = '/^\d{8}[Tt]\d{6}([Zz]|[+-]\d{2}:?\d{2})?$/';

    public function parse(string $input): ?ParseResult
    {
        $trimmed = $this->normalizeInput($input);
        if ($trimmed === '') {
            return null;
        }

        // Purely numeric strings of length 9–10 (Unix seconds) or 13 (ms) belong to UnixTimestampParser.
        // Also reject floats (e.g. 2432187.2 — Julian date) so Carbon does not parse them as Unix seconds.
        if (preg_match('/^\d+(\.\d+)?$/', $trimmed)) {
            // Exception: a 4-digit year only
            if (strlen($trimmed) !== 4) {
                return null;
            }
        }

        // Year only (4 digits): canonical 1 Jan 00:00:00 UTC. Carbon::parse('2024') is wrong here.
        if (preg_match('/^\d{4}$/', $trimmed)) {
            $year = (int) $trimmed;
            if ($year >= 1 && $year <= 9999) {
                $carbon = Carbon::createFromDate($year, 1, 1, 'UTC')->startOfDay();
                $segments = [new Segment('year', $trimmed, 0, strlen($trimmed))];

                return new ParseResult($carbon, 'iso-8601', 'year', $segments, ['year']);
            }
        }

        // Input must match ISO 8601 (or be very close). If our regexes do not match
        // (e.g. "Mon, 28 Dec 2025..."), it is another format — do not greedily Carbon::parse().
        if (!preg_match(self::REGEX_DATETIME, $trimmed)
            && !preg_match(self::REGEX_TIME_ONLY, $trimmed)
            && !preg_match(self::REGEX_COMPACT_DATETIME, $trimmed)) {
            return null;
        }

        try {
            $carbon = Carbon::parse($trimmed);
        } catch (Throwable) {
            return null;
        }

        $mask = $this->detectMask($trimmed);
        $presentKeys = $this->detectPresentKeys($trimmed);
        $segments = $this->buildSegmentsFromInput($trimmed);

        return new ParseResult($carbon, 'iso-8601', $mask, $segments, $presentKeys);
    }

    private function normalizeInput(string $input): string
    {
        $normalized = trim($input);

        // Handle urlencoded ISO 8601 strings like 2026-05-04T10%3A44%3A46Z
        if (str_contains($normalized, '%3A') || str_contains($normalized, '%3a')) {
            $normalized = urldecode($normalized);
        }

        // Strip common edge wrappers (quotes/brackets/parentheses) and
        // single leading colon often seen in prefixed log tokens.
        $normalized = trim($normalized, self::EDGE_NOISE_CHARS);
        $normalized = ltrim($normalized, ':');
        $normalized = preg_replace('/\s+([Zz]|[+-]\d{2}(?::?\d{2})?)$/', '$1', $normalized) ?? $normalized;
        $normalized = preg_replace('/[Zz]\s*([+-]\d{1,2}(?::?\d{1,2})?)$/', '$1', $normalized) ?? $normalized;
        $normalized = $this->normalizeShortTrailingOffset($normalized);

        return trim($normalized, self::EDGE_NOISE_CHARS);
    }

    private function normalizeShortTrailingOffset(string $input): string
    {
        $hasTimePart = preg_match('/(?:[Tt]|\s)\d{1,2}:\d{2}/', $input) === 1
            || preg_match('/^[Tt]?\d{1,2}:\d{2}/', $input) === 1;
        if (! $hasTimePart) {
            return $input;
        }

        if (preg_match('/([+-])(\d{1,2}):(\d{1,2})$/', $input, $m)) {
            $hours = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            $minutes = str_pad($m[3], 2, '0', STR_PAD_LEFT);

            return substr($input, 0, -strlen($m[0])) . $m[1] . $hours . ':' . $minutes;
        }

        if (preg_match('/([+-])(\d{1,2})$/', $input, $m)) {
            $hours = str_pad($m[2], 2, '0', STR_PAD_LEFT);

            return substr($input, 0, -strlen($m[0])) . $m[1] . $hours . ':00';
        }

        return $input;
    }

    /**
     * Build highlight segments from the raw string (substrings + offsets).
     *
     * @return array<int, Segment>
     */
    private function buildSegmentsFromInput(string $input): array
    {
        $segments = [];

        if (preg_match(self::REGEX_DATETIME, $input, $m, PREG_OFFSET_CAPTURE)) {
            $segments = $this->segmentsFromDateTimeMatch($m, $input);
        } elseif (preg_match(self::REGEX_TIME_ONLY, $input, $m, PREG_OFFSET_CAPTURE)) {
            $segments = $this->segmentsFromTimeOnlyMatch($m, $input);
        }

        return $segments;
    }

    /**
     * @param array<string, array{0: string, 1: int}> $m
     * @return array<int, Segment>
     */
    private function segmentsFromDateTimeMatch(array $m, string $input): array
    {
        $segments = [];
        $push = function (string $key, string $value, int $start, int $end) use (&$segments): void {
            if ($value !== '') {
                $segments[] = new Segment($key, $value, $start, $end);
            }
        };

        if ($this->matched($m, 'year')) {
            $push('year', $m['year'][0], $m['year'][1], $m['year'][1] + strlen($m['year'][0]));
        }
        if ($this->matched($m, 'month')) {
            $push('month', $m['month'][0], $m['month'][1], $m['month'][1] + strlen($m['month'][0]));
        }
        if ($this->matched($m, 'day')) {
            $push('day', $m['day'][0], $m['day'][1], $m['day'][1] + strlen($m['day'][0]));
        }
        if ($this->matched($m, 'time_sep')) {
            $push('separator', $m['time_sep'][0], $m['time_sep'][1], $m['time_sep'][1] + strlen($m['time_sep'][0]));
        }
        if ($this->matched($m, 'hour')) {
            $push('hour', $m['hour'][0], $m['hour'][1], $m['hour'][1] + strlen($m['hour'][0]));
        }
        if ($this->matched($m, 'minute')) {
            $val = ltrim($m['minute'][0], ':');
            $pos = $m['minute'][1] + 1;
            $push('minute', $val, $pos, $pos + strlen($val));
        }
        if ($this->matched($m, 'second')) {
            $val = ltrim($m['second'][0], ':');
            $pos = $m['second'][1] + 1;
            $push('second', $val, $pos, $pos + strlen($val));
        }
        if ($this->matched($m, 'frac')) {
            $push('millisecond', $m['frac'][0], $m['frac'][1], $m['frac'][1] + strlen($m['frac'][0]));
        }
        if ($this->matched($m, 'offset')) {
            $push('offset', $m['offset'][0], $m['offset'][1], $m['offset'][1] + strlen($m['offset'][0]));
        }

        return $segments;
    }

    /**
     * @param array<string, array{0: string, 1: int}> $m
     * @return array<int, Segment>
     */
    private function segmentsFromTimeOnlyMatch(array $m, string $input): array
    {
        $segments = [];
        $push = function (string $key, string $value, int $start, int $end) use (&$segments): void {
            if ($value !== '') {
                $segments[] = new Segment($key, $value, $start, $end);
            }
        };

        if ($this->matched($m, 'time_sep')) {
            $push('separator', $m['time_sep'][0], $m['time_sep'][1], $m['time_sep'][1] + strlen($m['time_sep'][0]));
        }
        if ($this->matched($m, 'hour')) {
            $push('hour', $m['hour'][0], $m['hour'][1], $m['hour'][1] + strlen($m['hour'][0]));
        }
        if ($this->matched($m, 'minute')) {
            $val = ltrim($m['minute'][0], ':');
            $pos = $m['minute'][1] + 1;
            $push('minute', $val, $pos, $pos + strlen($val));
        }
        if ($this->matched($m, 'second')) {
            $val = ltrim($m['second'][0], ':');
            $pos = $m['second'][1] + 1;
            $push('second', $val, $pos, $pos + strlen($val));
        }
        if ($this->matched($m, 'frac')) {
            $push('millisecond', $m['frac'][0], $m['frac'][1], $m['frac'][1] + strlen($m['frac'][0]));
        }
        if ($this->matched($m, 'offset')) {
            $push('offset', $m['offset'][0], $m['offset'][1], $m['offset'][1] + strlen($m['offset'][0]));
        }

        return $segments;
    }

    /**
     * @param array<string, array{0: string, 1: int}> $m
     */
    private function matched(array $m, string $name): bool
    {
        return isset($m[$name]) && is_array($m[$name]) && $m[$name][1] >= 0 && $m[$name][0] !== '';
    }

    /**
     * @return array<int, string>|null
     */
    private function detectPresentKeys(string $input): ?array
    {
        $keys = [];

        if (preg_match('/^\d{4}$/', $input)) {
            return ['year'];
        }
        if (preg_match('/^\d{4}-\d{2}$/', $input)) {
            return ['year', 'month'];
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $input) || preg_match('/^\d{8}([Tt]|\s|$)/', $input)) {
            $keys = ['year', 'month', 'day'];
        }

        // Time (with or without date, with or without T)
        if (preg_match('/(?:[Tt]|\s|^)\d{1,2}:\d{2}/', $input)) {
            $keys = array_merge($keys, ['hour', 'minute']);
            if (preg_match('/(?:[Tt]|\s|^)\d{1,2}:\d{2}:\d{2}/', $input)) {
                $keys[] = 'second';
            }
        }
        if (preg_match('/\.\d{1,9}([Zz]|[+-]|$)/', $input)) {
            $keys[] = 'millisecond';
        }
        if (preg_match('/(?:[Tt]|\s)\d{1,2}:\d{2}(?::\d{2})?(?:\.\d{1,9})?([Zz]|[+-]\d{2}(?::?\d{2})?)$/', $input)
            || preg_match('/^[Tt]?\d{1,2}:\d{2}(?::\d{2})?(?:\.\d{1,9})?([Zz]|[+-]\d{2}(?::?\d{2})?)$/', $input)) {
            $keys[] = 'offset';
        }

        return $keys !== [] ? $keys : null;
    }

    private function detectMask(string $input): string
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}([Tt]|\s)/', $input)) {
            return preg_match('/(?:[Tt]|\s)\d{1,2}:\d{2}(?::\d{2})?(?:\.\d{1,9})?([Zz]|[+-]\d{2}(?::?\d{2})?)$/', $input)
                ? 'date_time_tz'
                : 'date_time_utc';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $input)) {
            return 'date_only';
        }
        if (preg_match('/^\d{8}T\d{6}/', $input)) {
            return 'compact';
        }
        if (preg_match('/^[Tt]?\d{1,2}:\d{2}/', $input)) {
            return 'time_only';
        }

        return 'date_time_tz';
    }
}
