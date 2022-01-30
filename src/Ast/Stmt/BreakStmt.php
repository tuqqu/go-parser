<?php

declare(strict_types=1);

namespace GoParser\Ast\Stmt;

use GoParser\Ast\Expr\Expr;
use GoParser\Ast\Expr\Ident;
use GoParser\Ast\Keyword;

final class BreakStmt implements Stmt
{
    public function __construct(
        public readonly Keyword $keyword,
        public readonly ?Ident $label,
    ) {}
}
