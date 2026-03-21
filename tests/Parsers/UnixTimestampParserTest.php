<?php

namespace DTFormat\PhpDtformat\Tests\Parsers;

use DTFormat\PhpDtformat\Parsers\UnixTimestampParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UnixTimestampParserTest extends TestCase
{
    private UnixTimestampParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new UnixTimestampParser;
    }

    #[DataProvider('validSecondsProvider')]
    public function test_parses_seconds(string $input, int $expectedTimestamp): void
    {
        $result = $this->parser->parse($input);
        $this->assertNotNull($result);
        $this->assertSame('unix-timestamp', $result->formatSlug);
        $this->assertSame('seconds', $result->mask);
        $this->assertSame($expectedTimestamp, $result->carbon->timestamp);
    }

    public static function validSecondsProvider(): array
    {
        return [
            '10 digits' => ['1735408005', 1735408005],
            '10 digits 2025-04-23' => ['1745398182', 1745398182],
            '1 digit' => ['0', 0],
            'epoch' => ['0', 0],
        ];
    }

    #[DataProvider('validMillisecondsProvider')]
    public function test_parses_milliseconds(string $input, int $expectedSeconds, int $expectedMs): void
    {
        $result = $this->parser->parse($input);
        $this->assertNotNull($result);
        $this->assertSame('unix-timestamp', $result->formatSlug);
        $this->assertSame('milliseconds', $result->mask);
        $this->assertSame($expectedSeconds, $result->carbon->timestamp);
        $this->assertSame($expectedMs, $result->carbon->millisecond);
    }

    public static function validMillisecondsProvider(): array
    {
        return [
            '13 digits' => ['1735408005000', 1735408005, 0],
            'with ms' => ['1735408005123', 1735408005, 123],
        ];
    }

    public function test_trimmed_input(): void
    {
        $result = $this->parser->parse('  1735408005  ');
        $this->assertNotNull($result);
        $this->assertSame(1735408005, $result->carbon->timestamp);
    }

    #[DataProvider('invalidOrTypoProvider')]
    public function test_returns_null_on_invalid_or_typo(string $input): void
    {
        $result = $this->parser->parse($input);
        $this->assertNull($result);
    }

    public static function invalidOrTypoProvider(): array
    {
        return [
            'empty' => [''],
            'only spaces' => ['   '],
            'letter in middle' => ['1735408a05'],
            'leading minus' => ['-1735408005'],
            'decimal point' => ['1735408005.0'],
            'space inside' => ['173 5408005'],
            'too long 14 digits' => ['17354080050000'],
            'too long 20 digits' => ['17354080050000000000'],
            'mixed alphanumeric' => ['1735408005abc'],
            'only letters' => ['abcdefghij'],
            'hex looking' => ['0xdeadbeef'],
            'float string' => ['1735408005.5'],
        ];
    }

    public function test_11_digits_treated_as_milliseconds(): void
    {
        $result = $this->parser->parse('17354080050');
        $this->assertNotNull($result);
        $this->assertSame('milliseconds', $result->mask);
        $this->assertSame(17354080, $result->carbon->timestamp);
        $this->assertSame(50, $result->carbon->millisecond);
    }

    public function test_12_digits_milliseconds(): void
    {
        $result = $this->parser->parse('173540800500');
        $this->assertNotNull($result);
        $this->assertSame('milliseconds', $result->mask);
        $this->assertSame(173540800, $result->carbon->timestamp);
        $this->assertSame(500, $result->carbon->millisecond);
    }
}
