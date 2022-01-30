<?php

declare(strict_types=1);

namespace GoParser\Ast\Stmt;

use GoParser\Ast\ExprList;
use GoParser\Ast\Keyword;

final class ReturnStmt implements Stmt
{
    public function __construct(
        public readonly Keyword $keyword,
        public readonly ?ExprList $exprList,
    ) {}
}
