<?php

declare(strict_types=1);

namespace GoParser\Ast;

use GoParser\Ast\Expr\Expr;

final class RangeClause implements AstNode
{
    public function __construct(
        public readonly ExprList|IdentList|null $list,
        public readonly Keyword $keyword,
        public readonly Expr $expr,
    ) {}
}
