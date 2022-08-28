<?php

declare(strict_types=1);

namespace GoParser\Lexer;

use GoParser\Error;

final class LexError extends \Exception implements Error
{
    public readonly Position $pos;

    public function __construct(string $message, Position $pos)
    {
        parent::__construct($message);

        $this->pos = $pos;
    }

    public function __toString(): string
    {
        return \sprintf('%s: %s', $this->pos, $this->message);
    }
}
