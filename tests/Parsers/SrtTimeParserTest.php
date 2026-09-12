<?php

namespace DTFormat\PhpDtformat\Tests\Parsers;

use DTFormat\PhpDtformat\Parsers\SrtTimeParser;
use PHPUnit\Framework\TestCase;

class SrtTimeParserTest extends TestCase
{
    private SrtTimeParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new SrtTimeParser;
    }

    public function test_parses_srt_time(): void
    {
        $result = $this->parser->parse('01:00:15,920');
        $this->assertNotNull($result);
        $this->assertSame('srt-time', $result->formatSlug);
        $this->assertSame('01:00:15', $result->carbon->format('H:i:s'));
        $this->assertSame(920000, $result->carbon->micro);
    }

    public function test_returns_null_on_invalid_date(): void
    {
        $this->assertNull($this->parser->parse('not a date'));
    }
}
