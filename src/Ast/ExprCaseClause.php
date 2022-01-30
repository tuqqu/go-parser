<?php

declare(strict_types=1);

namespace GoParser\Ast;

final class ExprCaseClause implements CaseClause
{
    public function __construct(
        public readonly ExprSwitchCase|DefaultCase $case,
        public readonly StmtList $stmtList,
    ) {}
}
