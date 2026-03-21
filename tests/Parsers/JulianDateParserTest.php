<?php

namespace DTFormat\PhpDtformat\Tests\Parsers;

use DTFormat\PhpDtformat\Parsers\JulianDateParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class JulianDateParserTest extends TestCase
{
    private JulianDateParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new JulianDateParser;
    }

    /** JD 2440587.5 = 1970-01-01 00:00 UTC */
    public function test_parses_jd_epoch(): void
    {
        $result = $this->parser->parse('2440587.5');
        $this->assertNotNull($result);
        $this->assertSame('julian-date', $result->formatSlug);
        $this->assertSame('fractional', $result->mask);
        $c = $result->carbon;
        $this->assertSame(1970, $c->year);
        $this->assertSame(1, $c->month);
        $this->assertSame(1, $c->day);
    }

    public function test_parses_integer_jd(): void
    {
        $result = $this->parser->parse('2460400');
        $this->assertNotNull($result);
        $this->assertSame('julian-date', $result->formatSlug);
        $this->assertSame('integer', $result->mask);
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
            'below min' => ['1721425'],
            'above max' => ['5373485'],
            'letters' => ['2440587.5abc'],
            'negative' => ['-2440587'],
        ];
    }
}
