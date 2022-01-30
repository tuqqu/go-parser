<?php

declare(strict_types=1);

namespace GoParser\Ast;

use GoParser\Ast\Expr\StringLiteral;
use GoParser\Lexer\Position;

final class Punctuation implements AstNode
{
    use FromLexeme;

    public function __construct(
        public readonly Position $pos,
        public readonly string $value,
    ) {}
}
