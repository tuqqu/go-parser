<?php

declare(strict_types=1);

namespace GoParser\Ast;

use GoParser\Ast\Expr\Expr;

final class ExprList implements AstNode
{
    /**
     * @param Expr[] $exprs
     */
    public function __construct(
        public readonly array $exprs,
    ) {}
}
