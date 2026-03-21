<?php

namespace DTFormat\PhpDtformat;

use Carbon\CarbonInterface;
use DTFormat\PhpDtformat\Formatters\ChineseDateFormatter;
use DTFormat\PhpDtformat\Formatters\DotNetTicksFormatter;
use DTFormat\PhpDtformat\Formatters\ExcelSerialFormatter;
use DTFormat\PhpDtformat\Formatters\FiletimeFormatter;
use DTFormat\PhpDtformat\Formatters\FormatterInterface;
use DTFormat\PhpDtformat\Formatters\GpsTimeFormatter;
use DTFormat\PhpDtformat\Formatters\Iso8601Formatter;
use DTFormat\PhpDtformat\Formatters\Iso9075Formatter;
use DTFormat\PhpDtformat\Formatters\JulianDateFormatter;
use DTFormat\PhpDtformat\Formatters\LocalDateFormatter;
use DTFormat\PhpDtformat\Formatters\MacTimestampFormatter;
use DTFormat\PhpDtformat\Formatters\NtpTimestampFormatter;
use DTFormat\PhpDtformat\Formatters\OleDateFormatter;
use DTFormat\PhpDtformat\Formatters\PdfDateFormatter;
use DTFormat\PhpDtformat\Formatters\Rfc2822Formatter;
use DTFormat\PhpDtformat\Formatters\Rfc3339Formatter;
use DTFormat\PhpDtformat\Formatters\TextDateFormatter;
use DTFormat\PhpDtformat\Formatters\TireDotFormatter;
use DTFormat\PhpDtformat\Formatters\UnixMillisecondsFormatter;
use DTFormat\PhpDtformat\Formatters\UnixSecondsFormatter;
use DTFormat\PhpDtformat\Parsers\ChineseDateParser;
use DTFormat\PhpDtformat\Parsers\DotNetTicksParser;
use DTFormat\PhpDtformat\Parsers\ExcelSerialParser;
use DTFormat\PhpDtformat\Parsers\FiletimeParser;
use DTFormat\PhpDtformat\Parsers\GpsTimeParser;
use DTFormat\PhpDtformat\Parsers\Iso8601Parser;
use DTFormat\PhpDtformat\Parsers\Iso9075Parser;
use DTFormat\PhpDtformat\Parsers\JulianDateParser;
use DTFormat\PhpDtformat\Parsers\LocalDateParser;
use DTFormat\PhpDtformat\Parsers\MacTimestampParser;
use DTFormat\PhpDtformat\Parsers\MongoObjectIdParser;
use DTFormat\PhpDtformat\Parsers\NtpTimestampParser;
use DTFormat\PhpDtformat\Parsers\OleDateParser;
use DTFormat\PhpDtformat\Parsers\ParserInterface;
use DTFormat\PhpDtformat\Parsers\PdfDateParser;
use DTFormat\PhpDtformat\Parsers\Rfc2822Parser;
use DTFormat\PhpDtformat\Parsers\Rfc3339Parser;
use DTFormat\PhpDtformat\Parsers\SqlParser;
use DTFormat\PhpDtformat\Parsers\TextDateParser;
use DTFormat\PhpDtformat\Parsers\TireDotParser;
use DTFormat\PhpDtformat\Parsers\UnixTimestampParser;
use DTFormat\PhpDtformat\Parsers\UsDateParser;

class DateDetector
{
    /** @var array<string, ParserInterface> */
    private array $parsers;

    /** @var array<string, FormatterInterface> */
    private array $formatters;

    /**
     * @param array<string, ParserInterface>|null $parsers slug => parser (null = default set)
     * @param array<string, FormatterInterface>|null $formatters key => formatter (null = default set)
     */
    public function __construct(?array $parsers = null, ?array $formatters = null)
    {
        $this->parsers = $parsers ?? self::defaultParsers();
        $this->formatters = $formatters ?? self::defaultFormatters();
    }

    /**
     * @return array<string, ParserInterface>
     */
    public static function defaultParsers(): array
    {
        return [
            'iso-8601' => new Iso8601Parser,
            'unix-timestamp' => new UnixTimestampParser,
            'rfc-3339' => new Rfc3339Parser,
            'rfc-2822' => new Rfc2822Parser,
            'iso-9075' => new Iso9075Parser,
            'sql' => new SqlParser,
            'excel-serial' => new ExcelSerialParser,
            'filetime' => new FiletimeParser,
            'julian-date' => new JulianDateParser,
            'tire-dot' => new TireDotParser,
            'mac-timestamp' => new MacTimestampParser,
            'text-date' => new TextDateParser,
            'chinese-date' => new ChineseDateParser,
            'dot-net-ticks' => new DotNetTicksParser,
            'local-date' => new LocalDateParser,
            'us-date' => new UsDateParser,
            'ole-date' => new OleDateParser,
            'ntp-timestamp' => new NtpTimestampParser,
            'gps-time' => new GpsTimeParser,
            'mongo-objectid' => new MongoObjectIdParser,
            'pdf-date' => new PdfDateParser,
        ];
    }

    /**
     * @return array<string, FormatterInterface>
     */
    public static function defaultFormatters(): array
    {
        return [
            'text_date' => new TextDateFormatter,
            'local_date' => new LocalDateFormatter,
            'iso8601' => new Iso8601Formatter,
            'unix_seconds' => new UnixSecondsFormatter,
            'unix_milliseconds' => new UnixMillisecondsFormatter,
            'rfc3339' => new Rfc3339Formatter,
            'rfc2822' => new Rfc2822Formatter,
            'iso9075' => new Iso9075Formatter,
            'excel_serial' => new ExcelSerialFormatter,
            'filetime' => new FiletimeFormatter,
            'julian_date' => new JulianDateFormatter,
            'tire_dot' => new TireDotFormatter,
            'mac_timestamp' => new MacTimestampFormatter,
            'chinese_date' => new ChineseDateFormatter,
            'dot_net_ticks' => new DotNetTicksFormatter,
            'ole_date' => new OleDateFormatter,
            'ntp_timestamp' => new NtpTimestampFormatter,
            'gps_time' => new GpsTimeFormatter,
            'pdf_date' => new PdfDateFormatter,
        ];
    }

    /**
     * Detect all matching date formats for the given input string.
     *
     * @return array<int, ParseResult>
     */
    public function detect(string $input): array
    {
        $trimmed = trim($input);
        if ($trimmed === '') {
            return [];
        }

        $results = [];
        foreach ($this->parsers as $parser) {
            $result = $parser->parse($input);
            if ($result !== null) {
                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * Convert a Carbon instance to all supported output formats.
     *
     * @return array<int, array{key: string, value: string}>
     */
    public function allRepresentations(CarbonInterface $carbon): array
    {
        $out = [];

        foreach ($this->formatters as $key => $formatter) {
            $out[] = [
                'key' => $key,
                'value' => $formatter->format($carbon),
            ];
        }

        return $out;
    }

    /**
     * @return array<string, ParserInterface>
     */
    public function getParsers(): array
    {
        return $this->parsers;
    }

    /**
     * @return array<string, FormatterInterface>
     */
    public function getFormatters(): array
    {
        return $this->formatters;
    }
}
