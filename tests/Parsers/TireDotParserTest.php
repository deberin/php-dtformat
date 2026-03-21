<?php

namespace DTFormat\PhpDtformat\Tests\Parsers;

use DTFormat\PhpDtformat\Parsers\TireDotParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TireDotParserTest extends TestCase
{
    private TireDotParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new TireDotParser;
    }

    /** 2525 = week 25 of 2025 (DOT: first week = first Sunday) */
    public function test_parses_wwyy(): void
    {
        $result = $this->parser->parse('2525');
        $this->assertNotNull($result);
        $this->assertSame('tire-dot', $result->formatSlug);
        $c = $result->carbon;
        $this->assertSame(2025, $c->year);
        $this->assertSame(6, $c->month);
    }

    public function test_parses_year_00_as_2000(): void
    {
        $result = $this->parser->parse('0100');
        $this->assertNotNull($result);
        $this->assertSame(2000, $result->carbon->year);
    }

    public function test_parses_year_29_as_2029(): void
    {
        $result = $this->parser->parse('0129');
        $this->assertNotNull($result);
        $this->assertSame(2029, $result->carbon->year);
    }

    public function test_parses_year_30_as_1930(): void
    {
        $result = $this->parser->parse('0130');
        $this->assertNotNull($result);
        $this->assertSame(1930, $result->carbon->year);
    }

    public function test_trimmed_input(): void
    {
        $result = $this->parser->parse('  2525  ');
        $this->assertNotNull($result);
        $this->assertSame('tire-dot', $result->formatSlug);
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
            '3 digits' => ['255'],
            '5 digits' => ['25252'],
            'week 00' => ['0025'],
            'week 54' => ['5425'],
            'letters' => ['25a5'],
        ];
    }
}
