<?php

namespace DTFormat\PhpDtformat\Parsers;

use DTFormat\PhpDtformat\ParseResult;
use DTFormat\PhpDtformat\ParseSegmentBuilder;
use Carbon\Carbon;
use IntlDateFormatter;
use Throwable;

/**
 * Text/Human-readable dates with month/weekday names in any language.
 *
 * 3-layer pipeline:
 *  1. Carbon::parse()              — fast, covers English
 *  2. translateTimeString + cleanup — covers 35+ locales via Carbon dictionaries
 *  3. IntlDateFormatter brute-force — covers CLDR patterns for 38+ locales
 */
class TextDateParser implements ParserInterface
{
    private const CARBON_LOCALES = [
        'en', 'ru', 'uk', 'es', 'pt', 'fr', 'de', 'it', 'vi', 'nl',
        'pl', 'tr', 'ar', 'zh', 'ja', 'ko', 'cs', 'sv', 'da', 'fi',
        'nb', 'hu', 'ro', 'el', 'he', 'hi', 'bg', 'hr', 'sk', 'sl',
        'sr', 'ms', 'ca', 'id', 'th',
    ];

    private const INTL_LOCALES = [
        'en', 'en_US', 'en_GB',
        'es', 'es_MX', 'fr', 'fr_FR',
        'de', 'de_DE', 'pt', 'pt_BR', 'pt_PT',
        'ru', 'ru_RU', 'uk', 'uk_UA',
        'it', 'it_IT', 'nl', 'nl_NL',
        'pl', 'pl_PL', 'tr', 'tr_TR',
        'ar', 'ar_SA', 'zh', 'zh_CN', 'zh_TW',
        'ja', 'ja_JP', 'ko', 'ko_KR',
        'vi', 'vi_VN', 'th', 'th_TH',
        'id', 'id_ID', 'cs', 'cs_CZ',
        'sv', 'sv_SE', 'da', 'da_DK',
        'fi', 'fi_FI', 'nb', 'nb_NO',
        'hu', 'hu_HU', 'ro', 'ro_RO',
        'el', 'el_GR', 'he', 'he_IL',
        'hi', 'hi_IN', 'bg', 'bg_BG',
        'hr', 'hr_HR', 'sk', 'sk_SK',
        'sl', 'sl_SI', 'sr', 'sr_RS',
        'ms', 'ms_MY', 'ca', 'ca_ES',
    ];

    private const INTL_DATE_TYPES = [
        IntlDateFormatter::MEDIUM,
        IntlDateFormatter::LONG,
        IntlDateFormatter::FULL,
    ];

    private const INTL_TIME_TYPES = [
        IntlDateFormatter::NONE,
        IntlDateFormatter::SHORT,
        IntlDateFormatter::MEDIUM,
    ];

    /** Vietnamese colloquial word-months → numeric */
    private const VI_WORD_MONTHS = [
        'giêng' => 1, 'một' => 1, 'hai' => 2, 'ba' => 3,
        'tư' => 4, 'năm' => 5, 'sáu' => 6, 'bảy' => 7,
        'tám' => 8, 'chín' => 9, 'mười' => 10,
        'mười một' => 11, 'mười hai' => 12,
    ];

    public function parse(string $input): ?ParseResult
    {
        $trimmed = trim($input);

        if ($trimmed === '' || preg_match('/^\d{4}-\d{2}-\d{2}/', $trimmed)) {
            return null;
        }

        if (! preg_match('/[a-zA-Z\x{0080}-\x{FFFF}]/u', $trimmed)) {
            return null;
        }

        // ISO 8601 compact (20251228T190645Z) — only T/Z letters, not a text date
        if (preg_match('/^\d{8}T\d{6}/', $trimmed)) {
            return null;
        }

        // Chinese 年月日 — handled by ChineseDateParser
        if (preg_match('/[年月日]/u', $trimmed)) {
            return null;
        }

        // MongoDB ObjectId (24 hex chars)
        if (preg_match('/^[0-9a-f]{24}$/i', $trimmed)) {
            return null;
        }

        // Step 1: Carbon::parse — English
        $result = $this->tryDirectParse($trimmed);
        if ($result !== null) {
            return $result;
        }

        // Step 2: translateTimeString + cleanup per locale
        $result = $this->tryTranslateParse($trimmed);
        if ($result !== null) {
            return $result;
        }

        // Step 3: IntlDateFormatter brute-force (with optional Vietnamese normalization)
        return $this->tryIntlParse($trimmed);
    }

    private function tryDirectParse(string $input): ?ParseResult
    {
        $normalized = preg_replace('/\s+@\s+/', ' ', $input) ?? $input;

        try {
            $carbon = Carbon::parse($normalized);
            if ($carbon !== false) {
                return $this->buildResult($input, $carbon);
            }
        } catch (Throwable) {
        }

        return null;
    }

    private function tryTranslateParse(string $input): ?ParseResult
    {
        foreach (self::CARBON_LOCALES as $locale) {
            try {
                $en = Carbon::translateTimeString($input, $locale, 'en');
                if ($en === $input && $locale !== 'en') {
                    continue;
                }
                $clean = $this->normalizeTranslated($en);
                $carbon = Carbon::parse($clean);

                return $this->buildResult($input, $carbon);
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }

    private function tryIntlParse(string $input): ?ParseResult
    {
        $variants = [$input, $this->normalizeVietnamese($input)];
        $variants = array_unique($variants);

        foreach ($variants as $variant) {
            foreach (self::INTL_LOCALES as $locale) {
                foreach (self::INTL_DATE_TYPES as $dt) {
                    foreach (self::INTL_TIME_TYPES as $tt) {
                        $fmt = new IntlDateFormatter($locale, $dt, $tt);
                        $fmt->setLenient(true);
                        $pos = 0;
                        $ts = $fmt->parse($variant, $pos);
                        if ($ts !== false && $pos >= strlen($variant) * 0.6) {
                            $carbon = Carbon::createFromTimestamp($ts);

                            return $this->buildResult($input, $carbon);
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Cleanup artifacts left by Carbon::translateTimeString().
     */
    private function normalizeTranslated(string $en): string
    {
        $s = $en;
        // es/pt "de" → "of"
        $s = preg_replace('/\bof\b/i', '', $s);
        // en "at" connector
        $s = preg_replace('/\bat\b/i', '', $s);
        // Strip "year"/"years" artifacts after translateTimeString() for Russian locale inputs
        $s = preg_replace('/\byears?\b/i', '', $s);
        // fr "à"
        $s = preg_replace('/\bà\b/u', '', $s);
        // fr time: 20h00 → 20:00, 9h30 → 9:30
        $s = preg_replace('/(\d{1,2})h(\d{2})/i', '$1:$2', $s);
        // de "Uhr"
        $s = preg_replace('/\bUhr\b/i', '', $s);
        // collapse whitespace
        $s = preg_replace('/\s+/', ' ', trim($s));
        // trailing/leading punctuation junk
        $s = trim($s, " ,.");

        return $s;
    }

    /**
     * Normalize Vietnamese colloquial month names: "tháng ba" → "tháng 3".
     */
    private function normalizeVietnamese(string $input): string
    {
        $result = $input;
        // "mười một" and "mười hai" must be checked before "mười"
        foreach (self::VI_WORD_MONTHS as $word => $num) {
            $result = preg_replace('/tháng\s+' . preg_quote($word, '/') . '\b/ui', "tháng $num", $result);
        }

        return $result;
    }

    private function buildResult(string $originalInput, \Carbon\CarbonInterface $carbon): ParseResult
    {
        [$mask, $presentKeys] = $this->detectMaskAndKeys($originalInput);
        $segments = ParseSegmentBuilder::textDate($originalInput);

        return new ParseResult($carbon, 'text-date', $mask, $segments, $presentKeys);
    }

    /**
     * @return array{0: string, 1: string[]}
     */
    private function detectMaskAndKeys(string $input): array
    {
        $mask = 'date';
        $presentKeys = ['year', 'month', 'day'];

        $hasTime = preg_match('/\d{1,2}:\d{2}/', $input)
            || preg_match('/\d{1,2}h\d{2}/i', $input)
            || preg_match('/\b\d{1,2}\s*(am|pm)\b/i', $input);

        if ($hasTime) {
            $mask = 'datetime';
            $presentKeys = array_merge($presentKeys, ['hour', 'minute']);
            if (preg_match('/:\d{2}:\d{2}/', $input)) {
                $presentKeys[] = 'second';
            }
        }

        return [$mask, $presentKeys];
    }
}
