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
