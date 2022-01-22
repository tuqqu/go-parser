<?php

declare(strict_types=1);

namespace GoParser;

final class ToStdoutErrorHandler implements ErrorHandler
{
    public function onError(Error $err): void
    {
        \fwrite(\STDOUT, $err . "\n");
    }
}
