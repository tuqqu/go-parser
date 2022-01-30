<?php

declare(strict_types=1);

namespace GoParser\Ast\Expr;

use GoParser\Ast\ExprList;
use GoParser\Ast\Punctuation;

final class CallExpr implements PrimaryExpr
{
    public function __construct(
        public readonly Expr $expr,
        public readonly Punctuation $lParen,
        public readonly ExprList $args,
        public readonly ?Punctuation $ellipsis,
        public readonly Punctuation $rParen,
    ) {}
}
