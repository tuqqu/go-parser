<?php

declare(strict_types=1);

namespace GoParser\Ast\Stmt;

use GoParser\Ast\Keyword;
use GoParser\Ast\Punctuation;
use GoParser\Ast\TypeCaseClause;
use GoParser\Ast\TypeSwitchGuard;

final class TypeSwitchStmt implements SwitchStmt
{
    /**
     * @param TypeCaseClause[] $caseClauses
     */
    public function __construct(
        public readonly Keyword $keyword,
        public readonly ?SimpleStmt $init,
        public readonly TypeSwitchGuard $guard,
        public readonly Punctuation $lBrace,
        public readonly array $caseClauses,
        public readonly Punctuation $rBrace,
    ) {}
}
