<?php

declare(strict_types=1);

namespace GoParser\Ast;

final class CommClause implements CaseClause
{
    public function __construct(
        public readonly CommCase|DefaultCase $case,
        public readonly StmtList $stmtList,
    ) {}
}
