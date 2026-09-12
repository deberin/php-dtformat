<?php

namespace DTFormat\PhpDtformat\Tests\Parsers;

use DTFormat\PhpDtformat\Parsers\JsDateStringParser;
use PHPUnit\Framework\TestCase;

class JsDateStringParserTest extends TestCase
{
    private JsDateStringParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new JsDateStringParser;
    }

    public function test_parses_js_date_string(): void
    {
        $result = $this->parser->parse('Tue Dec 01 2026 17:07:18 GMT-0700 (Pacific Daylight Time)');
        $this->assertNotNull($result);
        $this->assertSame('js-date-string', $result->formatSlug);
        $this->assertSame('2026-12-01T17:07:18-07:00', $result->carbon->toIso8601String());
    }
    
    public function test_parses_js_date_string_with_extra_quotes(): void
    {
        $result = $this->parser->parse("'Tue Dec 01 2026 17:07:18 GMT-0700 (Pacific Daylight Time)'");
        $this->assertNotNull($result);
        $this->assertSame('2026-12-01T17:07:18-07:00', $result->carbon->toIso8601String());
    }

    public function test_returns_null_on_invalid_date(): void
    {
        $this->assertNull($this->parser->parse('not a date'));
    }
}
