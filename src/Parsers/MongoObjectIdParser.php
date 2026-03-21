<?php

namespace DTFormat\PhpDtformat\Parsers;

use DTFormat\PhpDtformat\ParseResult;
use DTFormat\PhpDtformat\Segment;
use Carbon\Carbon;
use Throwable;

/**
 * MongoDB ObjectId: 24 hex characters. First 4 bytes (8 hex) = Unix timestamp (big-endian).
 */
class MongoObjectIdParser implements ParserInterface
{
    public function parse(string $input): ?ParseResult
    {
        $trimmed = trim($input);
        if ($trimmed === '' || ! preg_match('/^[0-9a-fA-F]{24}$/', $trimmed)) {
            return null;
        }

        $timestamp = (int) hexdec(substr($trimmed, 0, 8));

        try {
            $carbon = Carbon::createFromTimestamp($timestamp, 'UTC');
        } catch (Throwable) {
            return null;
        }

        $segments = [
            new Segment('mongo_objectid_timestamp', substr($trimmed, 0, 8), 0, 8),
            new Segment('mongo_objectid_suffix', substr($trimmed, 8), 8, 24),
        ];

        return new ParseResult($carbon, 'mongo-objectid', null, $segments);
    }
}
