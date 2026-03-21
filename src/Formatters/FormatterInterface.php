<?php

namespace DTFormat\PhpDtformat\Formatters;

use Carbon\CarbonInterface;

interface FormatterInterface
{
    public function format(CarbonInterface $carbon): string;
}
