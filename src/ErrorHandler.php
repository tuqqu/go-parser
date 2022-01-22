<?php

declare(strict_types=1);

namespace GoParser;

interface ErrorHandler
{
    public function onError(Error $err): void;
}
