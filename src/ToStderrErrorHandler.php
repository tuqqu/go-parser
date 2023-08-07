<?php

declare(strict_types=1);

namespace GoParser;

use function fwrite;

use const STDERR;

final class ToStderrErrorHandler implements ErrorHandler
{
    public function onError(Error $err): void
    {
        fwrite(STDERR, $err . "\n");
    }
}
