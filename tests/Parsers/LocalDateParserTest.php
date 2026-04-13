<?php

namespace DTFormat\PhpDtformat\Tests\Parsers;

use DTFormat\PhpDtformat\Parsers\LocalDateParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LocalDateParserTest extends TestCase
{
    private LocalDateParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new LocalDateParser;
    }

    public function test_parses_dot_format_d_m_Y(): void
    {
        $result = $this->parser->parse('28.12.2025');
        $this->assertNotNull($result);
        $this->assertSame('local-date', $result->formatSlug);
        $this->assertSame('date', $result->mask);
        $c = $result->carbon;
        $this->assertSame(2025, $c->year);
        $this->assertSame(12, $c->month);
        $this->assertSame(28, $c->day);
    }

    public function test_parses_dot_format_with_time(): void
    {
        $result = $this->parser->parse('28.12.2025 19:06');
        $this->assertNotNull($result);
        $this->assertSame('local-date', $result->formatSlug);
        $this->assertSame('datetime', $result->mask);
        $c = $result->carbon;
        $this->assertSame(19, $c->hour);
        $this->assertSame(6, $c->minute);
    }

    public function test_parses_dashed_date_dot_time_with_fraction(): void
    {
        $result = $this->parser->parse('2025-10-15-15.59.00.202000');
        $this->assertNotNull($result);
        $this->assertSame('local-date', $result->formatSlug);
        $this->assertSame('datetime', $result->mask);
        $this->assertSame('2025-10-15 15:59:00.202000', $result->carbon->format('Y-m-d H:i:s.u'));
    }

    public function test_parses_slash_format(): void
    {
        $result = $this->parser->parse('15/1/2025');
        $this->assertNotNull($result);
        $this->assertSame('local-date', $result->formatSlug);
        $c = $result->carbon;
        $this->assertSame(2025, $c->year);
        $this->assertSame(1, $c->month);
        $this->assertSame(15, $c->day);
    }

    #[DataProvider('slashYmdProvider')]
    public function test_parses_slash_ymd_variants(string $input, string $expectedYmd, string $expectedTime): void
    {
        $result = $this->parser->parse($input);
        $this->assertNotNull($result, "Should parse slash Y/m/d input: {$input}");
        $this->assertSame('local-date', $result->formatSlug);
        $this->assertSame($expectedYmd, $result->carbon->format('Y-m-d'));
        $this->assertSame($expectedTime, $result->carbon->format('H:i:s'));
    }

    public static function slashYmdProvider(): array
    {
        return [
            'Y/m/d date only' => ['1985/10/09', '1985-10-09', '00:00:00'],
            'Y/m/d datetime with offset' => ['2026/03/13 00:00:00 +0100', '2026-03-13', '00:00:00'],
            'Y/m/d no leading zero month/day' => ['2026/4/3 18:44', '2026-04-03', '18:44:00'],
            'Y/m/d date only second sample' => ['2026/01/08', '2026-01-08', '00:00:00'],
        ];
    }

    #[DataProvider('compactFractionalProvider')]
    public function test_parses_compact_fractional_datetime(string $input, string $expectedUtc): void
    {
        $result = $this->parser->parse($input);
        $this->assertNotNull($result, "Should parse compact fractional datetime: {$input}");
        $this->assertSame('local-date', $result->formatSlug);
        $this->assertSame('datetime', $result->mask);
        $this->assertSame($expectedUtc, $result->carbon->copy()->utc()->format('Y-m-d H:i:s.u'));
    }

    public static function compactFractionalProvider(): array
    {
        return [
            'no offset' => ['20250618145338.549681', '2025-06-18 14:53:38.549681'],
            'short negative zero offset' => ['20260331074730.389038-000', '2026-03-31 07:47:30.389038'],
            'short positive offset' => ['20260407084432.902745+060', '2026-04-07 02:44:32.902745'],
        ];
    }

    #[DataProvider('colonDateProvider')]
    public function test_parses_colon_date_variants(string $input, string $expected): void
    {
        $result = $this->parser->parse($input);
        $this->assertNotNull($result, "Should parse colon date variant: {$input}");
        $this->assertSame('local-date', $result->formatSlug);
        $this->assertSame($expected, $result->carbon->format('Y-m-d H:i:s.uP'));
    }

    public static function colonDateProvider(): array
    {
        return [
            'colon date time' => ['2026:03:23 19:19:51', '2026-03-23 19:19:51.000000+00:00'],
            'colon date time second sample' => ['2020:02:16 14:48:08', '2020-02-16 14:48:08.000000+00:00'],
            'colon date only' => ['2026:04:02', '2026-04-02 00:00:00.000000+00:00'],
            'colon date with offset' => ['2026:03:23 19:34:52-07:00', '2026-03-23 19:34:52.000000-07:00'],
            'colon date with fraction and offset' => ['2026:02:06 15:46:07.165-06:00', '2026-02-06 15:46:07.165000-06:00'],
        ];
    }

    public function test_returns_null_for_text_month_formats(): void
    {
        $this->assertNull($this->parser->parse('17 March 2025'));
        $this->assertNull($this->parser->parse('March 17, 2025'));
    }

    #[DataProvider('invalidProvider')]
    public function test_returns_null_on_invalid(string $input): void
    {
        $result = $this->parser->parse($input);
        $this->assertNull($result);
    }

    public static function invalidProvider(): array
    {
        return [
            'empty' => [''],
            'iso Y-m-d' => ['2025-12-28'],
            'iso datetime' => ['2025-12-28 19:06:45'],
            'garbage' => ['not a date'],
            'only digits' => ['20251228'],
        ];
    }
}
