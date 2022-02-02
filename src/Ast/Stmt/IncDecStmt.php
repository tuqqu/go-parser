<?php

declare(strict_types=1);

namespace GoParser\Ast\Stmt;

use GoParser\Ast\Expr\Expr;
use GoParser\Ast\Operator;

final class IncDecStmt implements SimpleStmt
{
    public function __construct(
        public readonly Expr $lhs,
        public readonly Operator $op,
    ) {}
}
