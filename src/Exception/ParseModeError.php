<?php

declare(strict_types=1);

namespace GoParser\Exception;

use GoParser\ParseMode;
use BadMethodCallException;

use function sprintf;

final class ParseModeError extends BadMethodCallException
{
    public function __construct(ParseMode $expected, ParseMode $actual)
    {
        parent::__construct(sprintf(
            'Expected Parser to be initialised with Parse Mode "%s" , but got "%s"',
            $expected->name,
            $actual->name
        ));
    }
}
