<?php

namespace DTFormat\PhpDtformat\Tests\Parsers;

use DTFormat\PhpDtformat\Parsers\Rfc2822Parser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class Rfc2822ParserTest extends TestCase
{
    private Rfc2822Parser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new Rfc2822Parser;
    }

    #[DataProvider('validProvider')]
    public function test_parses_valid(string $input): void
    {
        $result = $this->parser->parse($input);
        $this->assertNotNull($result);
        $this->assertSame('rfc-2822', $result->formatSlug);
    }

    public static function validProvider(): array
    {
        return [
            'with comma' => ['Mon, 28 Dec 2025 19:06:45 +0300'],
            'without comma' => ['Mon 28 Dec 2025 19:06:45 +0300'],
            'lowercase day month' => ['tue, 28 dec 2025 19:06:45 GMT'],
            'with seconds' => ['Wed, 13 Mar 2026 07:34:19 +0300'],
        ];
    }

    public function test_carbon_values(): void
    {
        $result = $this->parser->parse('Sun, 28 Dec 2025 19:06:45 +0300');
        $this->assertNotNull($result);
        $expected = \Carbon\Carbon::create(2025, 12, 28, 16, 6, 45, 'UTC');
        $this->assertSame($expected->timestamp, $result->carbon->timestamp);
    }

    public function test_trimmed_input(): void
    {
        $result = $this->parser->parse('  Mon, 28 Dec 2025 19:06:45 +0300  ');
        $this->assertNotNull($result);
        $this->assertSame('rfc-2822', $result->formatSlug);
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
            'ISO style' => ['2025-12-28T19:06:45Z'],
            'no day of week' => ['28 Dec 2025 19:06:45 +0300'],
            'invalid month' => ['Mon, 28 Xxx 2025 19:06:45 +0300'],
            'trailing garbage' => ['Mon, 28 Dec 2025 19:06:45 +0300 x'],
        ];
    }
}
