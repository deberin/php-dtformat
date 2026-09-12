<?php

namespace DTFormat\PhpDtformat\Tests\Parsers;

use DTFormat\PhpDtformat\Parsers\ODataDatetimeParser;
use PHPUnit\Framework\TestCase;

class ODataDatetimeParserTest extends TestCase
{
    private ODataDatetimeParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new ODataDatetimeParser;
    }

    public function test_parses_odata_datetime(): void
    {
        $result = $this->parser->parse("datetime'2026-08-01T04:00:00'");
        $this->assertNotNull($result);
        $this->assertSame('odata-datetime', $result->formatSlug);
        $this->assertSame('2026-08-01 04:00:00', $result->carbon->format('Y-m-d H:i:s'));
    }

    public function test_parses_odata_datetime_url_encoded(): void
    {
        $result = $this->parser->parse("datetime'2026-08-01T04%3A00%3A00'");
        $this->assertNotNull($result);
        $this->assertSame('odata-datetime', $result->formatSlug);
        $this->assertSame('2026-08-01 04:00:00', $result->carbon->format('Y-m-d H:i:s'));
    }

    public function test_returns_null_on_invalid_date(): void
    {
        $this->assertNull($this->parser->parse('not a date'));
    }
}
