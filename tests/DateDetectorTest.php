<?php

namespace DTFormat\PhpDtformat\Tests;

use DTFormat\PhpDtformat\DateDetector;
use DTFormat\PhpDtformat\ParseResult;
use PHPUnit\Framework\TestCase;

class DateDetectorTest extends TestCase
{
    private DateDetector $detector;

    protected function setUp(): void
    {
        $this->detector = new DateDetector;
    }

    public function test_empty_input_returns_empty(): void
    {
        $this->assertSame([], $this->detector->detect(''));
        $this->assertSame([], $this->detector->detect('   '));
    }

    public function test_iso8601_detected(): void
    {
        $results = $this->detector->detect('2025-12-28T19:06:45Z');
        $slugs = array_map(fn (ParseResult $r) => $r->formatSlug, $results);
        $this->assertContains('iso-8601', $slugs);
    }

    public function test_unix_timestamp_detected(): void
    {
        $results = $this->detector->detect('1735408005');
        $slugs = array_map(fn (ParseResult $r) => $r->formatSlug, $results);
        $this->assertContains('unix-timestamp', $slugs);
    }

    public function test_rfc2822_detected(): void
    {
        $results = $this->detector->detect('Mon, 28 Dec 2025 19:06:45 +0300');
        $slugs = array_map(fn (ParseResult $r) => $r->formatSlug, $results);
        $this->assertContains('rfc-2822', $slugs);
    }

    public function test_excel_serial_detected(): void
    {
        $results = $this->detector->detect('45389.5');
        $slugs = array_map(fn (ParseResult $r) => $r->formatSlug, $results);
        $this->assertContains('excel-serial', $slugs);
    }

    public function test_mongo_objectid_detected(): void
    {
        $results = $this->detector->detect('674a1b2c3d4e5f6789abcdef');
        $slugs = array_map(fn (ParseResult $r) => $r->formatSlug, $results);
        $this->assertContains('mongo-objectid', $slugs);
    }

    public function test_chinese_date_detected(): void
    {
        $results = $this->detector->detect('2025年3月17日');
        $slugs = array_map(fn (ParseResult $r) => $r->formatSlug, $results);
        $this->assertContains('chinese-date', $slugs);
    }

    public function test_pdf_date_detected(): void
    {
        $results = $this->detector->detect("D:20180921141013-04'00'");
        $slugs = array_map(fn (ParseResult $r) => $r->formatSlug, $results);
        $this->assertContains('pdf-date', $slugs);
    }

    public function test_wcf_date_detected(): void
    {
        $results = $this->detector->detect('/Date(1777667541000+0000)/');
        $slugs = array_map(fn (ParseResult $r) => $r->formatSlug, $results);
        $this->assertContains('wcf-date', $slugs);
    }

    public function test_multiple_formats_can_match(): void
    {
        $results = $this->detector->detect('2025-12-28T19:06:45Z');
        $this->assertGreaterThanOrEqual(2, count($results), 'ISO 8601 input should match at least ISO-8601 and RFC-3339');
    }

    public function test_all_representations(): void
    {
        $results = $this->detector->detect('2025-12-28T19:06:45Z');
        $this->assertNotEmpty($results);

        $representations = $this->detector->allRepresentations($results[0]->carbon);
        $this->assertNotEmpty($representations);

        $keys = array_column($representations, 'key');
        $this->assertContains('iso8601', $keys);
        $this->assertContains('unix_seconds', $keys);
        $this->assertContains('rfc3339', $keys);
    }

    public function test_custom_parsers(): void
    {
        $detector = new DateDetector([
            'unix-timestamp' => new \DTFormat\PhpDtformat\Parsers\UnixTimestampParser,
        ]);

        $results = $detector->detect('1735408005');
        $this->assertCount(1, $results);
        $this->assertSame('unix-timestamp', $results[0]->formatSlug);

        $noResults = $detector->detect('2025-12-28T19:06:45Z');
        $this->assertEmpty($noResults);
    }

    public function test_nonsense_input_returns_empty_or_minimal(): void
    {
        $results = $this->detector->detect('hello world');
        $this->assertEmpty($results);
    }
}
