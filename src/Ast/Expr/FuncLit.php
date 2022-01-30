<?php

declare(strict_types=1);

namespace GoParser\Ast\Expr;

use GoParser\Ast\Stmt\BlockStmt;

final class FuncLit implements Literal
{
    public function __construct(
        public readonly FuncType $type,
        public readonly BlockStmt $body,
    ) {}
}
