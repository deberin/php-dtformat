<?php

namespace DTFormat\PhpDtformat\Tests\Parsers;

use DTFormat\PhpDtformat\Parsers\NtpTimestampParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NtpTimestampParserTest extends TestCase
{
    private NtpTimestampParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new NtpTimestampParser;
    }

    /** NTP 2208988800 = 1970-01-01 00:00:00 UTC (seconds since 1900-01-01) */
    public function test_parses_ntp_epoch_as_unix_epoch(): void
    {
        $result = $this->parser->parse('2208988800');
        $this->assertNotNull($result);
        $this->assertSame('ntp-timestamp', $result->formatSlug);
        $c = $result->carbon;
        $this->assertSame(1970, $c->year);
        $this->assertSame(1, $c->month);
        $this->assertSame(1, $c->day);
        $this->assertSame(0, $c->hour);
    }

    public function test_parses_9_digits(): void
    {
        $result = $this->parser->parse('394848000');
        $this->assertNotNull($result);
        $this->assertSame('ntp-timestamp', $result->formatSlug);
    }

    public function test_parses_10_digits(): void
    {
        $result = $this->parser->parse('3948480000');
        $this->assertNotNull($result);
        $this->assertSame('ntp-timestamp', $result->formatSlug);
    }

    public function test_trimmed_input(): void
    {
        $result = $this->parser->parse('  2208988800  ');
        $this->assertNotNull($result);
        $this->assertSame('ntp-timestamp', $result->formatSlug);
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
            '8 digits' => ['22089888'],
            '11 digits' => ['22089888000'],
            'letter' => ['220898880a'],
            'negative' => ['-2208988800'],
        ];
    }
}
