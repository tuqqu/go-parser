<?php

declare(strict_types=1);

namespace GoParser\Ast;

use GoParser\Ast\Expr\Expr;
use GoParser\Exception\InvalidArgument;

final class RangeClause implements AstNode
{
    public function __construct(
        public readonly ExprList|IdentList|null $list,
        public readonly ?Operator $op,
        public readonly Keyword $keyword,
        public readonly Expr $expr,
    ) {
        if ((bool) $op !== (bool) $list) {
            throw new InvalidArgument('Both list and operator must be null or neither');
        }
    }
}
