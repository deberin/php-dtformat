<?php

namespace DTFormat\PhpDtformat\Tests\Parsers;

use DTFormat\PhpDtformat\Parsers\ChineseDateParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ChineseDateParserTest extends TestCase
{
    private ChineseDateParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new ChineseDateParser;
    }

    public function test_parses_date_only(): void
    {
        $result = $this->parser->parse('2025年3月17日');
        $this->assertNotNull($result);
        $this->assertSame('chinese-date', $result->formatSlug);
        $this->assertSame('date', $result->mask);
        $c = $result->carbon;
        $this->assertSame(2025, $c->year);
        $this->assertSame(3, $c->month);
        $this->assertSame(17, $c->day);
    }

    public function test_parses_with_time_时(): void
    {
        $result = $this->parser->parse('2025年12月28日 19时06分');
        $this->assertNotNull($result);
        $this->assertSame('chinese-date', $result->formatSlug);
        $this->assertSame('datetime', $result->mask);
        $c = $result->carbon;
        $this->assertSame(19, $c->hour);
        $this->assertSame(6, $c->minute);
    }

    public function test_parses_with_time_colon(): void
    {
        $result = $this->parser->parse('2025年12月28日 19:06');
        $this->assertNotNull($result);
        $this->assertSame('datetime', $result->mask);
        $c = $result->carbon;
        $this->assertSame(19, $c->hour);
        $this->assertSame(6, $c->minute);
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
            'ISO style' => ['2025-12-28'],
            'invalid month 0' => ['2025年0月17日'],
            'invalid month 13' => ['2025年13月17日'],
            'invalid day 32' => ['2025年3月32日'],
        ];
    }
}
