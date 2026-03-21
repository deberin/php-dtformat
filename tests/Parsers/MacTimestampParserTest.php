<?php

namespace DTFormat\PhpDtformat\Tests\Parsers;

use DTFormat\PhpDtformat\Parsers\MacTimestampParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MacTimestampParserTest extends TestCase
{
    private MacTimestampParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new MacTimestampParser;
    }

    /** Mac 2082844800 = 1970-01-01 00:00 UTC */
    public function test_parses_mac_epoch_as_unix_epoch(): void
    {
        $result = $this->parser->parse('2082844800');
        $this->assertNotNull($result);
        $this->assertSame('mac-timestamp', $result->formatSlug);
        $c = $result->carbon;
        $this->assertSame(1970, $c->year);
        $this->assertSame(1, $c->month);
        $this->assertSame(1, $c->day);
    }

    public function test_parses_zero_as_1904_01_01(): void
    {
        $result = $this->parser->parse('0');
        $this->assertNotNull($result);
        $c = $result->carbon;
        $this->assertSame(1904, $c->year);
        $this->assertSame(1, $c->month);
        $this->assertSame(1, $c->day);
    }

    public function test_parses_9_digits(): void
    {
        $result = $this->parser->parse('100000000');
        $this->assertNotNull($result);
        $this->assertSame('mac-timestamp', $result->formatSlug);
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
            '11 digits' => ['20828448000'],
            'letter' => ['208284480a'],
        ];
    }
}
