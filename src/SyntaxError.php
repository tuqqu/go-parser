<?php

declare(strict_types=1);

namespace GoParser;

use GoParser\Lexer\Position;
use Exception;

use function sprintf;

class SyntaxError extends Exception implements Error
{
    public readonly Position $pos;

    public function __construct(string $message, Position $pos)
    {
        parent::__construct($message);

        $this->pos = $pos;
    }

    public function __toString(): string
    {
        return sprintf('%s syntax error: %s', $this->pos, $this->message);
    }
}
