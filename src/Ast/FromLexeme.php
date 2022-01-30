<?php

declare(strict_types=1);

namespace GoParser\Ast;

use GoParser\Lexer\Lexeme;

trait FromLexeme
{
    public static function fromLexeme(Lexeme $lexeme): static
    {
        return new static($lexeme->pos, $lexeme->literal ?? $lexeme->token->value);
    }
}
