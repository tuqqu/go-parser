<?php

declare(strict_types=1);

namespace GoParser;

interface Error
{
    public function __toString(): string;
}
