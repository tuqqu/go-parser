<?php

declare(strict_types=1);

namespace GoParser\Ast\Expr;

use GoParser\Ast\Punctuation;

final class FullSliceExpr implements SliceExpr
{
    public function __construct(
        public readonly Expr $expr,
        public readonly Punctuation $lParen,
        public readonly ?Expr $low,
        public readonly Punctuation $colon1,
        public readonly Expr $high,
        public readonly Punctuation $colon2,
        public readonly Expr $max,
        public readonly Punctuation $rParen,
    ) {}
}
