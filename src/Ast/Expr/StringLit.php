<?php

declare(strict_types=1);

namespace GoParser\Ast\Expr;

use GoParser\Ast\FromLexeme;
use GoParser\Lexer\Position;

final class StringLit implements Literal
{
    use FromLexeme;

    public function __construct(
        public readonly Position $pos,
        public readonly string $str,
    )
    {
    }
}
