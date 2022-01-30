<?php

declare(strict_types=1);

namespace GoParser;

use GoParser\Ast\Exception\InvalidArgument;
use GoParser\Lexer\Position;

class ParseError extends \Exception implements Error
{
    public readonly Position $pos;

    public function __construct(string $message, Position $pos)
    {
        parent::__construct($message);

        $this->pos = $pos;
    }

    public function __toString(): string
    {
        return \sprintf('%s ParseError: %s', $this->pos, $this->message);
    }
}
