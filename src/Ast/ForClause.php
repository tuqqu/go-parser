<?php

declare(strict_types=1);

namespace GoParser\Ast;

use GoParser\Ast\Stmt\SimpleStmt;

final class ForClause implements AstNode
{
    public function __construct(
        public readonly ?SimpleStmt $init,
        public readonly ?SimpleStmt $cond,
        public readonly ?SimpleStmt $post,
    ) {}
}
