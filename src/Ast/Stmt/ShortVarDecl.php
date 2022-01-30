<?php

declare(strict_types=1);

namespace GoParser\Ast\Stmt;

use GoParser\Ast\ExprList;
use GoParser\Ast\IdentList;
use GoParser\Ast\Operator;
use GoParser\Ast\Stmt\SimpleStmt;

final class ShortVarDecl implements SimpleStmt
{
    public function __construct(
        public readonly IdentList $idents,
        public readonly Operator $op,
        public readonly ExprList $exprs,
    ) {}
}
