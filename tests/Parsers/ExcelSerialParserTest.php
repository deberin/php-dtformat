<?php

namespace DTFormat\PhpDtformat\Tests\Parsers;

use DTFormat\PhpDtformat\Parsers\ExcelSerialParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ExcelSerialParserTest extends TestCase
{
    private ExcelSerialParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new ExcelSerialParser;
    }

    public function test_parses_serial_1_as_1900_01_01(): void
    {
        $result = $this->parser->parse('1');
        $this->assertNotNull($result);
        $this->assertSame('excel-serial', $result->formatSlug);
        $this->assertSame('date', $result->mask);
        $c = $result->carbon;
        $this->assertSame(1900, $c->year);
        $this->assertSame(1, $c->month);
        $this->assertSame(1, $c->day);
    }

    public function test_parses_25569_as_1970_01_01(): void
    {
        $result = $this->parser->parse('25569');
        $this->assertNotNull($result);
        $c = $result->carbon;
        $this->assertSame(1970, $c->year);
        $this->assertSame(1, $c->month);
        $this->assertSame(1, $c->day);
    }

    public function test_parses_with_fractional_as_datetime_mask(): void
    {
        $result = $this->parser->parse('45389.5');
        $this->assertNotNull($result);
        $this->assertSame('excel-serial', $result->formatSlug);
        $this->assertSame('datetime', $result->mask);
        $c = $result->carbon;
        $this->assertSame(12, $c->hour);
        $this->assertSame(0, $c->minute);
    }

    public function test_trimmed_input(): void
    {
        $result = $this->parser->parse('  45389  ');
        $this->assertNotNull($result);
        $this->assertSame('excel-serial', $result->formatSlug);
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
            'zero' => ['0'],
            'negative' => ['-1'],
            'below range' => ['0.5'],
            'above max' => ['2958466'],
            'letters' => ['45389abc'],
            'negative in range' => ['-45389'],
        ];
    }
}
