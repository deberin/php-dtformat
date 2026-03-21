<?php

namespace DTFormat\PhpDtformat\Parsers;

use DTFormat\PhpDtformat\ParseResult;

interface ParserInterface
{
    public function parse(string $input): ?ParseResult;
}
