<?php

namespace DTFormat\PhpDtformat\Tests\Parsers;

use DTFormat\PhpDtformat\Parsers\PdfDateParser;
use PHPUnit\Framework\TestCase;

class PdfDateParserTest extends TestCase
{
    private PdfDateParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new PdfDateParser;
    }

    public function test_parses_pdf_date_with_offset_and_quotes(): void
    {
        $result = $this->parser->parse("20180921141013-04'00'");
        $this->assertNotNull($result);
        $this->assertSame('pdf-date', $result->formatSlug);
        $this->assertSame('utc_or_offset', $result->mask);
        $this->assertSame('2018-09-21T14:10:13-04:00', $result->carbon->toIso8601String());
    }

    public function test_parses_pdf_date_with_D_prefix(): void
    {
        $result = $this->parser->parse("D:20180921141013-04'00'");
        $this->assertNotNull($result);
        $this->assertSame('pdf-date', $result->formatSlug);
        $this->assertSame('2018-09-21T14:10:13-04:00', $result->carbon->toIso8601String());
    }

    public function test_parses_pdf_date_with_z_offset(): void
    {
        $result = $this->parser->parse('20180921141013Z');
        $this->assertNotNull($result);
        $this->assertSame('pdf-date', $result->formatSlug);
        $this->assertSame('utc_or_offset', $result->mask);
        $this->assertSame('2018-09-21T14:10:13+00:00', $result->carbon->toIso8601String());
    }

    public function test_parses_pdf_date_without_offset(): void
    {
        $result = $this->parser->parse('20180921141013');
        $this->assertNotNull($result);
        $this->assertSame('pdf-date', $result->formatSlug);
        $this->assertSame('local', $result->mask);
        $this->assertSame('2018-09-21 14:10:13', $result->carbon->format('Y-m-d H:i:s'));
    }

    public function test_returns_null_on_invalid_pdf_date(): void
    {
        $this->assertNull($this->parser->parse('not a pdf date'));
        $this->assertNull($this->parser->parse('2018092114101'));
        $this->assertNull($this->parser->parse("20180921141013-04'00\""));
    }
}
