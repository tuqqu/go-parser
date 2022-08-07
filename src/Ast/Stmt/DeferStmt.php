<?php

declare(strict_types=1);

namespace GoParser\Ast\Stmt;

use GoParser\Ast\Expr\CallExpr;
use GoParser\Ast\Keyword;

final class DeferStmt implements Stmt
{
    public function __construct(
        public readonly Keyword $keyword,
        public readonly CallExpr $expr,
    ) {}
}
