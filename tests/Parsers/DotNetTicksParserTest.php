<?php

namespace DTFormat\PhpDtformat\Tests\Parsers;

use DTFormat\PhpDtformat\Parsers\DotNetTicksParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DotNetTicksParserTest extends TestCase
{
    private DotNetTicksParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new DotNetTicksParser;
    }

    /** 621355968000000000 = 1970-01-01 00:00:00 UTC */
    public function test_parses_unix_epoch_ticks(): void
    {
        $result = $this->parser->parse('621355968000000000');
        $this->assertNotNull($result);
        $this->assertSame('dot-net-ticks', $result->formatSlug);
        $c = $result->carbon;
        $this->assertSame(1970, $c->year);
        $this->assertSame(1, $c->month);
        $this->assertSame(1, $c->day);
        $this->assertSame(0, $c->hour);
        $this->assertSame(0, $c->second);
    }

    public function test_parses_18_digits(): void
    {
        $result = $this->parser->parse('638562392458670000');
        $this->assertNotNull($result);
        $this->assertSame('dot-net-ticks', $result->formatSlug);
        $c = $result->carbon;
        $this->assertSame(2024, $c->year);
    }

    public function test_parses_15_digits(): void
    {
        $result = $this->parser->parse('621355968000000');
        $this->assertNotNull($result);
        $this->assertSame('dot-net-ticks', $result->formatSlug);
    }

    public function test_trimmed_input(): void
    {
        $result = $this->parser->parse('  621355968000000000  ');
        $this->assertNotNull($result);
        $this->assertSame('dot-net-ticks', $result->formatSlug);
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
            '14 digits' => ['62135596800000'],
            '20 digits' => ['6213559680000000000'],
            'letter' => ['62135596800000000a'],
            'negative' => ['-621355968000000000'],
        ];
    }
}
