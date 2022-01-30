<?php

declare(strict_types=1);

namespace GoParser\Ast\Expr;

use GoParser\Ast\Punctuation;

final class GroupExpr implements Operand
{
    public function __construct(
        public readonly Punctuation $lParen,
        public readonly Expr $expr,
        public readonly Punctuation $rParen,
    ) {}
}
