<?php

declare(strict_types=1);

namespace GoParser\Ast\Stmt;

use GoParser\Ast\AstNode;
use GoParser\Ast\Expr\Expr;
use GoParser\Ast\Keyword;
use GoParser\Ast\Punctuation;

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
