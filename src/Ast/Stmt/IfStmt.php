<?php

declare(strict_types=1);

namespace GoParser\Ast\Stmt;

use GoParser\Exception\InvalidArgument;
use GoParser\Ast\Expr\Expr;
use GoParser\Ast\Keyword;

/**
 * Both standalone If statement and nested If inside the "else if" clause.
 */
final class IfStmt implements Stmt
{
    public function __construct(
        public readonly Keyword $if,
        public readonly ?SimpleStmt $init,
        public readonly Expr $condition,
        public readonly BlockStmt $ifBody,
        public readonly ?Keyword $else,
        public readonly BlockStmt|self|null $elseBody,
    ) {
        if ((bool) $else !== (bool) $elseBody) {
            throw new InvalidArgument('Both "else" keyword and the body must be present or neither.');
        }
    }
}
