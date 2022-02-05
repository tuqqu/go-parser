<?php

declare(strict_types=1);

namespace GoParser\Exception;

final class InvalidArgument extends \InvalidArgumentException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
