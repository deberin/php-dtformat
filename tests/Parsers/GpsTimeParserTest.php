<?php

namespace DTFormat\PhpDtformat\Tests\Parsers;

use DTFormat\PhpDtformat\Parsers\GpsTimeParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GpsTimeParserTest extends TestCase
{
    private GpsTimeParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new GpsTimeParser;
    }

    /** GPS week 0 = 1980-01-06 00:00:00 UTC */
    public function test_parses_week_zero(): void
    {
        $result = $this->parser->parse('0');
        $this->assertNotNull($result);
        $this->assertSame('gps-time', $result->formatSlug);
        $c = $result->carbon;
        $this->assertSame(1980, $c->year);
        $this->assertSame(1, $c->month);
        $this->assertContains($c->day, [6, 7]);
    }

    public function test_parses_week_with_space_and_seconds(): void
    {
        $result = $this->parser->parse('2290 345600');
        $this->assertNotNull($result);
        $this->assertSame('gps-time', $result->formatSlug);
        $c = $result->carbon;
        $this->assertSame(2023, $c->year);
        $this->assertSame(12, $c->month);
        $this->assertSame(1, $c->day);
        $this->assertLessThanOrEqual(1, $c->hour);
    }

    public function test_parses_week_with_dot_seconds(): void
    {
        $result = $this->parser->parse('2290.0');
        $this->assertNotNull($result);
        $this->assertSame('gps-time', $result->formatSlug);
        $c = $result->carbon;
        $this->assertSame(2023, $c->year);
    }

    public function test_parses_week_only(): void
    {
        $result = $this->parser->parse('2290');
        $this->assertNotNull($result);
        $this->assertSame('gps-time', $result->formatSlug);
    }

    public function test_trimmed_input(): void
    {
        $result = $this->parser->parse('  2290 345600  ');
        $this->assertNotNull($result);
        $this->assertSame('gps-time', $result->formatSlug);
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
            'seconds out of range' => ['2290 604800'],
            'letters' => ['2290 34560a'],
            'no separator' => ['2290345600'],
        ];
    }
}
