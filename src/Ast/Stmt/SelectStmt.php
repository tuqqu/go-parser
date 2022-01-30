<?php

declare(strict_types=1);

namespace GoParser\Ast\Stmt;

use GoParser\Ast\CommClause;
use GoParser\Ast\Keyword;
use GoParser\Ast\Punctuation;

final class SelectStmt implements Stmt
{
    /**
     * @param CommClause[] $caseClauses
     */
    public function __construct(
        public readonly Keyword $keyword,
        public readonly Punctuation $lBrace,
        public readonly array $caseClauses,
        public readonly Punctuation $rBrace,
    ) {}
}
