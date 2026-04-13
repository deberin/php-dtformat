<?php

namespace DTFormat\PhpDtformat\Tests\Parsers;

use DTFormat\PhpDtformat\Parsers\TextDateParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TextDateParserTest extends TestCase
{
    private TextDateParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new TextDateParser;
    }

    #[DataProvider('validEnglishProvider')]
    public function test_parses_english(string $input, string $expectedDate): void
    {
        $result = $this->parser->parse($input);
        $this->assertNotNull($result, "Failed to parse: $input");
        $this->assertSame('text-date', $result->formatSlug);
        $this->assertSame($expectedDate, $result->carbon->format('Y-m-d'));
    }

    public static function validEnglishProvider(): array
    {
        return [
            'MMM-dd-yyyy time' => ['Oct-10-2024 00:00:00', '2024-10-10'],
            'MMM dd, yyyy time' => ['Dec 31, 2019 23:59:59', '2019-12-31'],
            'month day, year @ time with milliseconds' => ['Mar 21, 2026 @ 14:20:33.500', '2026-03-21'],
            'month day, year @ time 2' => ['Apr 1, 2026 @ 16:06:22.469', '2026-04-01'],
            'month day ordinal' => ['16th January 2026', '2026-01-16'],
            'month ordinal year, time' => ['January 16th 2026, 11:40:48 pm', '2026-01-16'],
            'weekday, month dd, time' => ['Friday, 16 January 2026, 11:40:48 pm', '2026-01-16'],
            'nov. abbrev' => ['nov. 13, 2025 17:00:00', '2025-11-13'],
        ];
    }

    #[DataProvider('validGermanItalianProvider')]
    public function test_parses_german_and_italian(string $input, string $expectedDate): void
    {
        $result = $this->parser->parse($input);
        $this->assertNotNull($result, "Failed to parse: $input");
        $this->assertSame('text-date', $result->formatSlug);
        $this->assertSame($expectedDate, $result->carbon->format('Y-m-d'));
    }

    public static function validGermanItalianProvider(): array
    {
        return [
            'it month day year' => ['14 marzo 2026', '2026-03-14'],
            'de dotted month umlaut' => ['14. März 2026', '2026-03-14'],
            'de weekday time' => ['Freitag, 14. März 2025 15:30', '2025-03-14'],
        ];
    }

    #[DataProvider('validFrenchProvider')]
    public function test_parses_french(string $input, string $expectedDate): void
    {
        $result = $this->parser->parse($input);
        $this->assertNotNull($result, "Failed to parse: $input");
        $this->assertSame('text-date', $result->formatSlug);
        $this->assertSame($expectedDate, $result->carbon->format('Y-m-d'));
    }

    public static function validFrenchProvider(): array
    {
        return [
            'fr abbrev dot time' => ['janv. 01, 2005 00:00:00', '2005-01-01'],
            'fr abbrev à time' => ['23 janv. 2026 à 15:02', '2026-01-23'],
            'fr dec abbrev' => ['26 déc. 2025, 09:56:00', '2025-12-26'],
            'fr weekday h-time' => ['samedi 9 mai 2026 20h00', '2026-05-09'],
            'fr weekday h-time 2' => ['jeudi 12 mars 2026 20h00', '2026-03-12'],
        ];
    }

    #[DataProvider('validEsPtProvider')]
    public function test_parses_spanish_portuguese(string $input, string $expectedDate): void
    {
        $result = $this->parser->parse($input);
        $this->assertNotNull($result, "Failed to parse: $input");
        $this->assertSame('text-date', $result->formatSlug);
        $this->assertSame($expectedDate, $result->carbon->format('Y-m-d'));
    }

    public static function validEsPtProvider(): array
    {
        return [
            'es de...de' => ['26 de enero de 2026, 19:16', '2026-01-26'],
            'pt de fev. de' => ['2 de fev. de 2026, 18:51', '2026-02-02'],
        ];
    }

    #[DataProvider('validVietnameseProvider')]
    public function test_parses_vietnamese(string $input, string $expectedDate): void
    {
        $result = $this->parser->parse($input);
        $this->assertNotNull($result, "Failed to parse: $input");
        $this->assertSame('text-date', $result->formatSlug);
        $this->assertSame($expectedDate, $result->carbon->format('Y-m-d'));
    }

    public static function validVietnameseProvider(): array
    {
        return [
            'vi tháng ba (word)' => ['05 tháng ba 2026 09:44', '2026-03-05'],
        ];
    }

    public function test_has_segments(): void
    {
        $result = $this->parser->parse('January 16th 2026, 11:40:48 pm');
        $this->assertNotNull($result);
        $this->assertNotEmpty($result->segments);
        $keys = array_map(fn ($s) => $s->key, $result->segments);
        $this->assertContains('month_name', $keys);
        $this->assertContains('year', $keys);
        $this->assertContains('day', $keys);
        $this->assertContains('hour', $keys);
    }

    public function test_mask_datetime_for_time_input(): void
    {
        $result = $this->parser->parse('Jan 16, 2026 23:40:48');
        $this->assertNotNull($result);
        $this->assertSame('datetime', $result->mask);
        $this->assertContains('hour', $result->presentKeys);
        $this->assertContains('second', $result->presentKeys);
    }

    public function test_mask_date_for_date_only_input(): void
    {
        $result = $this->parser->parse('16th January 2026');
        $this->assertNotNull($result);
        $this->assertSame('date', $result->mask);
        $this->assertNotContains('hour', $result->presentKeys);
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
            'ISO date' => ['2025-12-28'],
            'ISO datetime' => ['2025-12-28T19:06:45Z'],
            'pure digits' => ['1735408005'],
            'numeric dots' => ['28.12.2025'],
        ];
    }
}
