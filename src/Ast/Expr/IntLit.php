<?php

declare(strict_types=1);

namespace GoParser\Ast\Expr;

use GoParser\Ast\FromLexeme;
use GoParser\Lexer\Position;

final class IntLit implements Literal
{
    use FromLexeme;

    public function __construct(
        public readonly Position $pos,
        public readonly string $digits,
    )
    {
    }
}
