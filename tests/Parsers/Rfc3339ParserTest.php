<?php

namespace DTFormat\PhpDtformat\Tests\Parsers;

use DTFormat\PhpDtformat\Parsers\Rfc3339Parser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class Rfc3339ParserTest extends TestCase
{
    private Rfc3339Parser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new Rfc3339Parser;
    }

    #[DataProvider('validUtcProvider')]
    public function test_parses_utc(string $input): void
    {
        $result = $this->parser->parse($input);
        $this->assertNotNull($result);
        $this->assertSame('rfc-3339', $result->formatSlug);
        $this->assertSame('utc', $result->mask);
    }

    public static function validUtcProvider(): array
    {
        return [
            'Z' => ['2025-12-28T19:06:45Z'],
            'lowercase z' => ['2025-12-28T19:06:45z'],
            'with milliseconds' => ['2026-03-13T07:34:19.867Z'],
        ];
    }

    #[DataProvider('validOffsetProvider')]
    public function test_parses_offset(string $input): void
    {
        $result = $this->parser->parse($input);
        $this->assertNotNull($result);
        $this->assertSame('rfc-3339', $result->formatSlug);
        $this->assertSame('offset', $result->mask);
    }

    public static function validOffsetProvider(): array
    {
        return [
            'plus colon' => ['2026-03-13T07:34:19+03:00'],
            'minus' => ['2025-12-28T19:06:45-05:00'],
            'no colon' => ['2025-12-28T19:06:45+0300'],
        ];
    }

    public function test_carbon_values(): void
    {
        $result = $this->parser->parse('2025-12-28T19:06:45Z');
        $this->assertNotNull($result);
        $c = $result->carbon;
        $this->assertSame(2025, $c->year);
        $this->assertSame(12, $c->month);
        $this->assertSame(28, $c->day);
        $this->assertSame(19, $c->hour);
        $this->assertSame(6, $c->minute);
        $this->assertSame(45, $c->second);
    }

    public function test_trimmed_input(): void
    {
        $result = $this->parser->parse('  2025-12-28T19:06:45Z  ');
        $this->assertNotNull($result);
        $this->assertSame('rfc-3339', $result->formatSlug);
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
            'only spaces' => ['   '],
            'date only no T' => ['2025-12-28'],
            'space instead of T' => ['2025-12-28 19:06:45Z'],
            'no timezone' => ['2025-12-28T19:06:45'],
            'trailing garbage' => ['2025-12-28T19:06:45Z x'],
            'leading garbage' => ['x 2025-12-28T19:06:45Z'],
            'invalid date' => ['2025-13-01T00:00:00Z'],
        ];
    }
}
