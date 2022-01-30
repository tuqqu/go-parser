<?php

declare(strict_types=1);

namespace GoParser\Ast\Expr;

final class SelectorExpr implements PrimaryExpr
{
    public function __construct(
        public readonly Expr $expr,
        public readonly Ident $selector,
    ) {}
}
