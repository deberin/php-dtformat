<?php

namespace DTFormat\PhpDtformat\Parsers;

use DTFormat\PhpDtformat\ParseResult;
use DTFormat\PhpDtformat\Segment;
use Carbon\Carbon;
use Throwable;

/**
 * .NET Ticks: 100-nanosecond intervals since 0001-01-01 00:00:00 (UTC).
 * 1 tick = 100 ns. 1970-01-01 00:00 UTC = 621355968000000000 ticks.
 */
class DotNetTicksParser implements ParserInterface
{
    private const TICKS_PER_SECOND = 10_000_000;

    private const UNIX_EPOCH_TICKS = 621355968000000000;

    public function parse(string $input): ?ParseResult
    {
        $trimmed = trim($input);
        if ($trimmed === '' || ! ctype_digit($trimmed)) {
            return null;
        }

        $len = strlen($trimmed);
        if ($len < 15 || $len > 19) {
            return null;
        }

        try {
            $secondsPart = $len > 7 ? (int) substr($trimmed, 0, -7) : 0;
            $unixSeconds = $secondsPart - (int) (self::UNIX_EPOCH_TICKS / self::TICKS_PER_SECOND);
            if ($unixSeconds < -62135596800 || $unixSeconds > 253402300799) {
                return null;
            }
            $carbon = Carbon::createFromTimestamp($unixSeconds, 'UTC');
            if ($len > 7) {
                $subsecTicks = (int) substr($trimmed, -7);
                $carbon->addMicroseconds((int) round($subsecTicks / 10));
            }
        } catch (Throwable) {
            return null;
        }

        $segments = [new Segment('dot_net_ticks', $trimmed, 0, $len)];

        return new ParseResult($carbon, 'dot-net-ticks', null, $segments);
    }
}
