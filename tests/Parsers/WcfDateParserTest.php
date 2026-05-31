<?php

namespace DTFormat\PhpDtformat\Tests\Parsers;

use DTFormat\PhpDtformat\Parsers\WcfDateParser;
use PHPUnit\Framework\TestCase;

class WcfDateParserTest extends TestCase
{
    public function test_parses_wcf_date(): void
    {
        $parser = new WcfDateParser;
        $result = $parser->parse('/Date(1777667541000)/');
        $this->assertNotNull($result);
        $this->assertSame('wcf-date', $result->formatSlug);
        $this->assertSame(1777667541, $result->carbon->timestamp);
    }

    public function test_parses_wcf_date_with_escapes(): void
    {
        $parser = new WcfDateParser;
        $result = $parser->parse('\/Date(1788238800000)\/');
        $this->assertNotNull($result);
        $this->assertSame(1788238800, $result->carbon->timestamp);
    }
    
    public function test_parses_wcf_date_with_offset(): void
    {
        $parser = new WcfDateParser;
        $result = $parser->parse('/Date(1777667541000+0000)/');
        $this->assertNotNull($result);
        $this->assertSame(1777667541, $result->carbon->timestamp);
    }
}
