<?php

namespace DTFormat\PhpDtformat\Tests\Parsers;

use DTFormat\PhpDtformat\Parsers\MongoObjectIdParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MongoObjectIdParserTest extends TestCase
{
    private MongoObjectIdParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new MongoObjectIdParser;
    }

    /** ObjectId with first 8 hex = 1735408005 (2024-12-28 19:06:45 UTC) */
    public function test_parses_objectid_extracts_timestamp(): void
    {
        $result = $this->parser->parse('674a1b2c3d4e5f6789abcdef');
        $this->assertNotNull($result);
        $this->assertSame('mongo-objectid', $result->formatSlug);
        $c = $result->carbon;
        $this->assertSame(2024, $c->year);
        $this->assertSame(11, $c->month);
        $this->assertSame(29, $c->day);
    }

    /** 000000000000000000000000 = Unix 0 = 1970-01-01 */
    public function test_parses_zero_timestamp_objectid(): void
    {
        $result = $this->parser->parse('000000000000000000000000');
        $this->assertNotNull($result);
        $this->assertSame('mongo-objectid', $result->formatSlug);
        $c = $result->carbon;
        $this->assertSame(1970, $c->year);
        $this->assertSame(1, $c->month);
        $this->assertSame(1, $c->day);
    }

    public function test_parses_uppercase_hex(): void
    {
        $result = $this->parser->parse('674A1B2C3D4E5F6789ABCDEF');
        $this->assertNotNull($result);
        $this->assertSame('mongo-objectid', $result->formatSlug);
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
            '23 chars' => ['674a1b2c3d4e5f6789abcde'],
            '25 chars' => ['674a1b2c3d4e5f6789abcdef0'],
            'non hex' => ['674a1b2c3d4e5f6789abcdefg'],
        ];
    }
}
