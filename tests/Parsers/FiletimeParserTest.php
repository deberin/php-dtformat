<?php

namespace DTFormat\PhpDtformat\Tests\Parsers;

use DTFormat\PhpDtformat\Parsers\FiletimeParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FiletimeParserTest extends TestCase
{
    private FiletimeParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new FiletimeParser;
    }

    /** 116444736000000000 = 1970-01-01 00:00:00 UTC (100-ns ticks since 1601-01-01) */
    public function test_parses_epoch_filetime(): void
    {
        $result = $this->parser->parse('116444736000000000');
        $this->assertNotNull($result);
        $this->assertSame('filetime', $result->formatSlug);
        $c = $result->carbon;
        $this->assertSame(1970, $c->year);
        $this->assertSame(1, $c->month);
        $this->assertSame(1, $c->day);
        $this->assertSame(0, $c->hour);
        $this->assertSame(0, $c->second);
    }

    public function test_parses_14_digits(): void
    {
        $result = $this->parser->parse('11644473600000');
        $this->assertNotNull($result);
        $this->assertSame('filetime', $result->formatSlug);
    }

    public function test_parses_19_digits(): void
    {
        $result = $this->parser->parse('133312896000000000');
        $this->assertNotNull($result);
        $this->assertSame('filetime', $result->formatSlug);
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
            '10 digits unix' => ['1735408005'],
            '13 digits unix ms' => ['1735408005000'],
            'letter' => ['11644473600000000a'],
            'negative' => ['-116444736000000000'],
        ];
    }
}
