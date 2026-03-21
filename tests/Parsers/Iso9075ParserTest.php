<?php

namespace DTFormat\PhpDtformat\Tests\Parsers;

use DTFormat\PhpDtformat\Parsers\Iso9075Parser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class Iso9075ParserTest extends TestCase
{
    private Iso9075Parser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new Iso9075Parser;
    }

    public function test_parses_date_only(): void
    {
        $result = $this->parser->parse('2025-12-28');
        $this->assertNotNull($result);
        $this->assertSame('iso-9075', $result->formatSlug);
        $this->assertSame('date', $result->mask);
    }

    #[DataProvider('validDatetimeProvider')]
    public function test_parses_datetime(string $input): void
    {
        $result = $this->parser->parse($input);
        $this->assertNotNull($result);
        $this->assertSame('iso-9075', $result->formatSlug);
        $this->assertSame('datetime', $result->mask);
    }

    public static function validDatetimeProvider(): array
    {
        return [
            'HH:MM:SS' => ['2025-12-28 19:06:45'],
            'HH:MM' => ['2025-12-28 19:06'],
            'with frac' => ['2025-12-28 19:06:45.123'],
        ];
    }

    public function test_carbon_values(): void
    {
        $result = $this->parser->parse('2025-12-28 19:06:45');
        $this->assertNotNull($result);
        $c = $result->carbon;
        $this->assertSame(2025, $c->year);
        $this->assertSame(12, $c->month);
        $this->assertSame(28, $c->day);
        $this->assertSame(19, $c->hour);
        $this->assertSame(6, $c->minute);
        $this->assertSame(45, $c->second);
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
            'T separator' => ['2025-12-28T19:06:45'],
            'invalid month' => ['2025-13-01'],
            'invalid day' => ['2025-12-32'],
            'garbage' => ['not a date'],
        ];
    }
}
