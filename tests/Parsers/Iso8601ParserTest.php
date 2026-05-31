<?php

namespace DTFormat\PhpDtformat\Tests\Parsers;

use DTFormat\PhpDtformat\Parsers\Iso8601Parser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class Iso8601ParserTest extends TestCase
{
    private Iso8601Parser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new Iso8601Parser;
    }

    #[DataProvider('validDateOnlyProvider')]
    public function test_parses_date_only(string $input): void
    {
        $result = $this->parser->parse($input);
        $this->assertNotNull($result);
        $this->assertSame('iso-8601', $result->formatSlug);
        $this->assertSame('date_only', $result->mask);
    }

    public static function validDateOnlyProvider(): array
    {
        return [
            'YYYY-MM-DD' => ['2025-12-28'],
            'another' => ['2026-01-01'],
        ];
    }

    #[DataProvider('validDateTimeTzProvider')]
    public function test_parses_date_time_with_timezone(string $input): void
    {
        $result = $this->parser->parse($input);
        $this->assertNotNull($result);
        $this->assertSame('iso-8601', $result->formatSlug);
        $this->assertSame('date_time_tz', $result->mask);
    }

    public static function validDateTimeTzProvider(): array
    {
        return [
            'with Z' => ['2025-12-28T19:06:45Z'],
            'with offset plus' => ['2026-03-13T07:34:19+03:00'],
            'with offset minus' => ['2025-12-28T19:06:45-05:00'],
            'with offset no colon' => ['2025-12-28T19:06:45+0300'],
            'with milliseconds' => ['2026-03-13T07:34:19.867+03:00'],
            'with microseconds and offset' => ['2020-01-28 09:55:41.428347+00:00'],
            'with 7-digit fraction and offset' => ['2026-04-13 08:00:00.0000000+00:00'],
            'single minute digit in offset' => ['2026-03-23T13:50:38.0834137+00:0'],
            'Z with trailing offset' => ['2024-03-29T14:46:03Z+05:30'],
            'Z with hour-only offset' => ['2026-04-06T23:59:59.000Z+10'],
            'Z with single-digit hour offset' => ['2026-04-06T10:25:25Z+1'],
            'single-digit hour with minutes offset' => ['2026-03-02T06:00+1:00'],
            'lowercase t and z with ms' => ['2025-12-09t08:41:54.817z'],
        ];
    }

    #[DataProvider('validDateTimeUtcProvider')]
    public function test_parses_date_time_without_timezone(string $input): void
    {
        $result = $this->parser->parse($input);
        $this->assertNotNull($result);
        $this->assertSame('iso-8601', $result->formatSlug);
        $this->assertSame('date_time_utc', $result->mask);
    }

    public static function validDateTimeUtcProvider(): array
    {
        return [
            'T separator' => ['2025-12-28T19:06:45'],
            'space separator' => ['2025-12-28 19:06:45'],
            'T separator with microseconds' => ['2025-12-09T15:39:11.089638'],
            'T separator with 7-digit fraction' => ['2026-03-06T01:14:49.0367591'],
            'space separator with 7-digit fraction' => ['2026-03-25 18:30:00.0000000'],
        ];
    }

    public function test_parses_compact_format(): void
    {
        $result = $this->parser->parse('20251228T190645Z');
        $this->assertNotNull($result);
        $this->assertSame('iso-8601', $result->formatSlug);
        $this->assertSame('compact', $result->mask);
    }

    public function test_parses_year_only(): void
    {
        $result = $this->parser->parse('2006');
        $this->assertNotNull($result);
        $this->assertSame('iso-8601', $result->formatSlug);
        $this->assertNotNull($result->presentKeys);
        $this->assertSame(['year'], $result->presentKeys);
    }

    public function test_parses_year_month_only(): void
    {
        $result = $this->parser->parse('2026-03');
        $this->assertNotNull($result);
        $this->assertSame(2026, $result->carbon->year);
        $this->assertSame(3, $result->carbon->month);
        $this->assertSame(['year', 'month'], $result->presentKeys);
    }

    public function test_trimmed_input(): void
    {
        $result = $this->parser->parse('  2025-12-28  ');
        $this->assertNotNull($result);
        $this->assertSame('2025-12-28', $result->carbon->format('Y-m-d'));
    }

    #[DataProvider('edgeNoiseIsoProvider')]
    public function test_parses_iso_with_edge_noise(string $input, string $expectedIsoPrefix): void
    {
        $result = $this->parser->parse($input);
        $this->assertNotNull($result, "Input should parse after edge-noise normalization: {$input}");
        $this->assertSame('iso-8601', $result->formatSlug);
        $this->assertStringStartsWith($expectedIsoPrefix, $result->carbon->toIso8601String());
    }

    public static function edgeNoiseIsoProvider(): array
    {
        return [
            'trailing parenthesis' => ['2026-03-21T12:56:03.264+00:00)', '2026-03-21T12:56:03'],
            'leading bracket' => ['[2026-08-01T00:00:00', '2026-08-01T00:00:00'],
            'single quotes around input' => ["'2023-05-01T00:00:00Z'", '2023-05-01T00:00:00'],
            'double quotes around input' => ['"2026-03-16T12:00:00.000-07:00"', '2026-03-16T12:00:00'],
            'dangling trailing quote' => ['2026-03-24T05:00:00-04:00"', '2026-03-24T05:00:00'],
            'leading colon' => [':2025-12-05 15:46:04.838 +00', '2025-12-05T15:46:04'],
            'urlencoded Z' => ['2026-05-04T10%3A44%3A46Z', '2026-05-04T10:44:46'],
            'urlencoded offset' => ['2026-05-04T10%3A44%3A46%2B03%3A00', '2026-05-04T10:44:46'],
        ];
    }

    #[DataProvider('invalidOrTypoProvider')]
    public function test_returns_null_on_invalid_or_typo(string $input): void
    {
        $result = $this->parser->parse($input);
        $this->assertNull($result);
    }

    /**
     * Inputs the parser must reject (null).
     * Carbon accepts some invalid strings — only cases Carbon actually rejects are listed here.
     */
    public static function invalidOrTypoProvider(): array
    {
        return [
            'empty' => [''],
            'only spaces' => ['   '],
            'garbage' => ['not a date'],
            'wrong order MM-DD-YYYY' => ['12-28-2025'],
            'invalid month 13' => ['2025-13-01'],
            'invalid day 32' => ['2025-12-32'],
            'dot instead of hyphen' => ['2025.12.28'],
            'invalid time hour 25' => ['2025-12-28T25:00:00'],
            'invalid time minute 60' => ['2025-12-28T23:60:00'],
            'invalid offset' => ['2025-12-28T19:06:45+99:00'],
            'trailing garbage' => ['2025-12-28T00:00:00Z garbage'],
            'leading garbage' => ['garbage 2025-12-28'],
            '10 digits Unix timestamp' => ['1745398182'],
            '9 digits Unix timestamp' => ['173540800'],
            '13 digits Unix ms' => ['1735408005000'],
        ];
    }

    /**
     * Per-input presentKeys: which parts were specified in the string.
     * (Used by UIs to show a value vs. dash for each segment.)
     */
    #[DataProvider('segmentPresentKeysProvider')]
    public function test_segment_present_keys_match_input(string $input, array $expectedPresentKeys): void
    {
        $result = $this->parser->parse($input);
        $this->assertNotNull($result, "Input should parse: {$input}");
        $this->assertSame($expectedPresentKeys, $result->presentKeys, "presentKeys for: {$input}");
    }

    public static function segmentPresentKeysProvider(): array
    {
        return [
            'only year' => ['2006', ['year']],
            'year and month' => ['2026-03', ['year', 'month']],
            'date only' => ['2025-12-28', ['year', 'month', 'day']],
            'date and time with Z, no ms' => ['2025-12-28T19:06:45Z', ['year', 'month', 'day', 'hour', 'minute', 'second', 'offset']],
            'date and time with Z and ms' => ['2025-12-28T19:06:45.123Z', ['year', 'month', 'day', 'hour', 'minute', 'second', 'millisecond', 'offset']],
            'lowercase t and z with ms' => ['2025-12-09t08:41:54.817z', ['year', 'month', 'day', 'hour', 'minute', 'second', 'millisecond', 'offset']],
            'date and time with offset' => ['2026-03-13T07:34:19.867+03:00', ['year', 'month', 'day', 'hour', 'minute', 'second', 'millisecond', 'offset']],
            'date and time without offset' => ['2025-12-28T19:06:45', ['year', 'month', 'day', 'hour', 'minute', 'second']],
            'date and time with space, no offset' => ['2025-12-28 19:06:45', ['year', 'month', 'day', 'hour', 'minute', 'second']],
            'only time hour minute' => ['09:30', ['hour', 'minute']],
            'only time hour minute second' => ['09:30:15', ['hour', 'minute', 'second']],
            'only time with one digit hour' => ['9:30', ['hour', 'minute']],
            'only time with T prefix' => ['T09:30', ['hour', 'minute']],
        ];
    }

    public function test_carbon_values_match_input(): void
    {
        $result = $this->parser->parse('2026-03-13T07:34:19.867+03:00');
        $this->assertNotNull($result);
        $c = $result->carbon;
        $this->assertSame(2026, $c->year);
        $this->assertSame(3, $c->month);
        $this->assertSame(13, $c->day);
        $this->assertSame(7, $c->hour);
        $this->assertSame(34, $c->minute);
        $this->assertSame(19, $c->second);
        $this->assertSame(867, $c->millisecond);
    }
}
