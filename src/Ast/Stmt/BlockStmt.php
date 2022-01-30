<?php

declare(strict_types=1);

namespace GoParser\Ast\Stmt;

use GoParser\Ast\Punctuation;
use GoParser\Ast\StmtList;

final class BlockStmt implements Stmt
{
    public function __construct(
        public readonly Punctuation $leftBrace,
        public readonly StmtList $stmtList,
        public readonly Punctuation $rightBrace,
    ) {}
}
