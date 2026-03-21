<?php

namespace DTFormat\PhpDtformat\Parsers;

use DTFormat\PhpDtformat\ParseResult;
use DTFormat\PhpDtformat\Segment;
use Carbon\Carbon;
use Throwable;

/**
 * Mac (HFS+) timestamp: seconds since 1904-01-01 00:00:00 UTC.
 * 32-bit range: 0 .. 2147483647 (covers up to 2038).
 */
class MacTimestampParser implements ParserInterface
{
    private const MAC_EPOCH_OFFSET = 2082844800; // seconds 1904-01-01 to 1970-01-01

    public function parse(string $input): ?ParseResult
    {
        $trimmed = trim($input);
        if ($trimmed === '' || ! ctype_digit($trimmed)) {
            return null;
        }

        $len = strlen($trimmed);
        if ($len < 1 || $len > 10) {
            return null;
        }

        $macSeconds = (int) $trimmed;
        if ($macSeconds < 0 || $macSeconds > 2147483647) {
            return null;
        }

        try {
            $unixSeconds = $macSeconds - self::MAC_EPOCH_OFFSET;
            $carbon = Carbon::createFromTimestamp($unixSeconds, 'UTC');
        } catch (Throwable) {
            return null;
        }

        $segments = [new Segment('mac_seconds', $trimmed, 0, $len)];

        return new ParseResult($carbon, 'mac-timestamp', null, $segments);
    }
}
