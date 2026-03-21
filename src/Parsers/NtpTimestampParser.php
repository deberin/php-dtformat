<?php

namespace DTFormat\PhpDtformat\Parsers;

use DTFormat\PhpDtformat\ParseResult;
use DTFormat\PhpDtformat\Segment;
use Carbon\Carbon;
use Throwable;

/**
 * NTP timestamp (seconds part): seconds since 1900-01-01 00:00:00 UTC.
 * 32-bit unsigned. Full NTP has also a 32-bit fraction; we only parse the seconds part.
 */
class NtpTimestampParser implements ParserInterface
{
    private const NTP_EPOCH_OFFSET = 2208988800;

    public function parse(string $input): ?ParseResult
    {
        $trimmed = trim($input);
        if ($trimmed === '' || ! ctype_digit($trimmed)) {
            return null;
        }

        $len = strlen($trimmed);
        if ($len < 9 || $len > 10) {
            return null;
        }

        $ntpSeconds = (int) $trimmed;
        if ($ntpSeconds > 4294967295) {
            return null;
        }

        try {
            $unixSeconds = $ntpSeconds - self::NTP_EPOCH_OFFSET;
            $carbon = Carbon::createFromTimestamp($unixSeconds, 'UTC');
        } catch (Throwable) {
            return null;
        }

        $segments = [new Segment('ntp_seconds', $trimmed, 0, $len)];

        return new ParseResult($carbon, 'ntp-timestamp', null, $segments);
    }
}
