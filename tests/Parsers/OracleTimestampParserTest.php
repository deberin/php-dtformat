<?php

namespace DTFormat\PhpDtformat\Tests\Parsers;

use DTFormat\PhpDtformat\Parsers\OracleTimestampParser;
use PHPUnit\Framework\TestCase;

class OracleTimestampParserTest extends TestCase
{
    private OracleTimestampParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new OracleTimestampParser;
    }

    public function test_parses_oracle_timestamp(): void
    {
        $result = $this->parser->parse('14-FEB-17 10.31.17.447000000 PM');
        $this->assertNotNull($result);
        $this->assertSame('oracle-timestamp', $result->formatSlug);
        $this->assertSame('2017-02-14 22:31:17', $result->carbon->format('Y-m-d H:i:s'));
        $this->assertSame(447000, $result->carbon->micro);
    }
    
    public function test_parses_oracle_timestamp_without_microseconds(): void
    {
        $result = $this->parser->parse('03-SEP-23 12.03.00 PM');
        $this->assertNotNull($result);
        $this->assertSame('2023-09-03 12:03:00', $result->carbon->format('Y-m-d H:i:s'));
    }

    public function test_returns_null_on_invalid_date(): void
    {
        $this->assertNull($this->parser->parse('not a date'));
    }
}
