<?php

namespace DTFormat\PhpDtformat\Tests\Parsers;

use DTFormat\PhpDtformat\Parsers\OdbcTimestampParser;
use PHPUnit\Framework\TestCase;

class OdbcTimestampParserTest extends TestCase
{
    private OdbcTimestampParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new OdbcTimestampParser;
    }

    public function test_parses_odbc_timestamp(): void
    {
        $result = $this->parser->parse("{ts '2026-07-01 09:13:48'}");
        $this->assertNotNull($result);
        $this->assertSame('odbc-timestamp', $result->formatSlug);
        $this->assertSame('2026-07-01 09:13:48', $result->carbon->format('Y-m-d H:i:s'));
    }

    public function test_returns_null_on_invalid_date(): void
    {
        $this->assertNull($this->parser->parse('not a date'));
    }
}
