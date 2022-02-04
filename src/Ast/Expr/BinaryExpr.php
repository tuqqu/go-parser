<?php

declare(strict_types=1);

namespace GoParser\Ast\Expr;

use GoParser\Ast\Operator;

final class BinaryExpr implements Expr
{
    public function __construct(
        public readonly Expr $lExpr,
        public readonly Operator $op,
        public readonly Expr $rExpr,
    ) {}
}
