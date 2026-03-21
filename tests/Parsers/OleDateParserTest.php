<?php

namespace DTFormat\PhpDtformat\Tests\Parsers;

use DTFormat\PhpDtformat\Parsers\OleDateParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class OleDateParserTest extends TestCase
{
    private OleDateParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new OleDateParser;
    }

    /** OLE 0 = 1899-12-30 */
    public function test_parses_zero_as_1899_12_30(): void
    {
        $result = $this->parser->parse('0');
        $this->assertNotNull($result);
        $this->assertSame('ole-date', $result->formatSlug);
        $this->assertSame('date', $result->mask);
        $c = $result->carbon;
        $this->assertSame(1899, $c->year);
        $this->assertSame(12, $c->month);
        $this->assertSame(30, $c->day);
    }

    /** OLE 2 = 1900-01-01 */
    public function test_parses_2_as_1900_01_01(): void
    {
        $result = $this->parser->parse('2');
        $this->assertNotNull($result);
        $c = $result->carbon;
        $this->assertSame(1900, $c->year);
        $this->assertSame(1, $c->month);
        $this->assertSame(1, $c->day);
    }

    public function test_parses_with_fractional_as_datetime_mask(): void
    {
        $result = $this->parser->parse('45390.75');
        $this->assertNotNull($result);
        $this->assertSame('ole-date', $result->formatSlug);
        $this->assertSame('datetime', $result->mask);
        $c = $result->carbon;
        $this->assertSame(18, $c->hour);
        $this->assertSame(0, $c->minute);
    }

    public function test_trimmed_input(): void
    {
        $result = $this->parser->parse('  45390  ');
        $this->assertNotNull($result);
        $this->assertSame('ole-date', $result->formatSlug);
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
            'negative' => ['-1'],
            'above max' => ['2958467'],
            'letters' => ['45390abc'],
        ];
    }
}
