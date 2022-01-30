<?php

declare(strict_types=1);

namespace GoParser\Ast;

use GoParser\Ast\Stmt\RecvStmt;
use GoParser\Ast\Stmt\SendStmt;

final class CommCase implements CaseLabel
{
    public function __construct(
        public readonly Keyword $keyword,
        public readonly SendStmt|RecvStmt $stmt,
        public readonly Punctuation $colon,
    ) {}
}
