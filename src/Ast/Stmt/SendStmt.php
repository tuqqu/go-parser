<?php

declare(strict_types=1);

namespace GoParser\Ast\Stmt;

use GoParser\Ast\Expr\Expr;
use GoParser\Ast\Operator;

final class SendStmt implements SimpleStmt
{
    public function __construct(
        public readonly Expr $channel,
        public readonly Operator $op,
        public readonly Expr $expr,
    ) {}
}
