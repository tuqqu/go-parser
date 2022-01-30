<?php

declare(strict_types=1);

namespace GoParser\Ast\Stmt;

use GoParser\Ast\Expr\Expr;
use GoParser\Ast\ExprList;
use GoParser\Ast\IdentList;

final class RecvStmt implements Stmt
{
    public function __construct(
        public readonly ExprList|IdentList|null $list,
        public readonly Expr $expr,
    ) {}
}
