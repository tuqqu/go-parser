<?php

declare(strict_types=1);

namespace GoParser\Ast;

use GoParser\Lexer\Position;

final class Operator
{
    use FromLexeme;

    public function __construct(
        public readonly Position $pos,
        public readonly string $value,
    ) {}
}
