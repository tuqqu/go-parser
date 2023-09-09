<?php

declare(strict_types=1);

namespace GoParser\Ast;

use GoParser\Lexer\Lexeme;
use GoParser\Lexer\Position;

trait FromLexeme
{
    public static function fromLexeme(Lexeme $lexeme): static
    {
        return new static($lexeme->pos, $lexeme->literal ?? $lexeme->token->value);
    }

    abstract public function __construct(Position $pos, string $literal);
}
