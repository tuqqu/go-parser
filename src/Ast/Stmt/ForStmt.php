<?php

declare(strict_types=1);

namespace GoParser\Ast\Stmt;

use GoParser\Ast\Exception\InvalidArgument;
use GoParser\Ast\Expr\Expr;
use GoParser\Ast\Keyword;

/**
 * All for variations:
 *
 * 1. $iteration is null         for {}
 * 2. $iteration is Expr         for expr {}
 * 3. $iteration is ForClause    for expr; expr; expr {}
 * 4. $iteration is RangeClause  for range_expr {}
 *
 */
final class ForStmt implements Stmt
{
    public function __construct(
        public readonly Keyword $keyword,
        public readonly Expr|ForClause|RangeClause|null $iteration,
        public readonly BlockStmt $body,
    ) {}
}
