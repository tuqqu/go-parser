<?php

declare(strict_types=1);

namespace GoParser\Ast\Expr;

final class TypeAssertionExpr implements PrimaryExpr
{
    public function __construct(
        public readonly Expr $expr,
        public readonly ParenType $type,
    ) {}
}
