<?php

declare(strict_types=1);

namespace GoParser\Ast;

final class TypeCaseClause implements CaseClause
{
    public function __construct(
        public readonly TypeSwitchCase|DefaultCase $case,
        public readonly StmtList $stmtList,
    ) {}
}
