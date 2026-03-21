<?php

namespace DTFormat\PhpDtformat;

use Carbon\CarbonInterface;

final readonly class ParseResult
{
    /**
     * @param array<int, Segment> $segments
     * @param array<int, string>|null $presentKeys segment keys present in the input; null = all parts specified
     */
    public function __construct(
        public CarbonInterface $carbon,
        public string $formatSlug,
        public ?string $mask = null,
        public array $segments = [],
        public ?array $presentKeys = null,
    ) {}
}
