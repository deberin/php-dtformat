<?php

namespace DTFormat\PhpDtformat\Tests\Parsers;

use DTFormat\PhpDtformat\Parsers\SqlParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SqlParserTest extends TestCase
{
    private SqlParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new SqlParser;
    }

    public function test_parses_date_only(): void
    {
        $result = $this->parser->parse('2025-12-28');
        $this->assertNotNull($result);
        $this->assertSame('sql', $result->formatSlug);
        $this->assertSame('date', $result->mask);
    }

    public function test_parses_datetime(): void
    {
        $result = $this->parser->parse('2025-12-28 19:06:45');
        $this->assertNotNull($result);
        $this->assertSame('sql', $result->formatSlug);
        $this->assertSame('datetime', $result->mask);
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
            'only spaces' => ['   '],
            'T separator' => ['2025-12-28T19:06:45'],
            'garbage' => ['not a date'],
        ];
    }
}
