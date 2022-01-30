<?php

declare(strict_types=1);

namespace GoParser\Ast\Expr;

use GoParser\Ast\Punctuation;

final class IndexExpr implements PrimaryExpr
{
    public function __construct(
        public readonly Expr $expr,
        public readonly Punctuation $lParen,
        public readonly Expr $index,
        public readonly Punctuation $rParen,
    ) {}
}
