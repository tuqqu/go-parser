<?php

declare(strict_types=1);

namespace GoParser\Ast;

use GoParser\Ast\Stmt\Stmt;

final class StmtList implements AstNode
{
    /**
     * @param Stmt[] $stmts
     */
    public function __construct(
        public readonly array $stmts,
    ) {}
}
