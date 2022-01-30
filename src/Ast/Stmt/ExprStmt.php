<?php

declare(strict_types=1);

namespace GoParser\Ast\Stmt;

use GoParser\Ast\Expr\Expr;

final class ExprStmt implements SimpleStmt
{
    public function __construct(
        public readonly Expr $expr,
    ) {}
}
