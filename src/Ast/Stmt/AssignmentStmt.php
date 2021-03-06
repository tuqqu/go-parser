<?php

declare(strict_types=1);

namespace GoParser\Ast\Stmt;

use GoParser\Ast\ExprList;
use GoParser\Ast\Operator;

final class AssignmentStmt implements SimpleStmt
{
    public function __construct(
        public readonly ExprList $lhs,
        public readonly Operator $op,
        public readonly ExprList $rhs,
    ) {}
}
