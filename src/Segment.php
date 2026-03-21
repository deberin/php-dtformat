<?php

namespace DTFormat\PhpDtformat;

/**
 * A slice of the raw input: substring and its semantic role (year, month, …).
 * start/end are byte offsets in the original string for UI highlighting.
 */
final readonly class Segment
{
    public function __construct(
        public string $key,
        public string $value,
        public int $start = 0,
        public int $end = 0,
    ) {}
}
