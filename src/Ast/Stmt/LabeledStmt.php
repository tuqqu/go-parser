<?php

declare(strict_types=1);

namespace GoParser\Ast\Stmt;

use GoParser\Ast\Expr\Ident;
use GoParser\Ast\Punctuation;

final class LabeledStmt implements Stmt
{
    public function __construct(
        public readonly Ident $label,
        public readonly Punctuation $colon,
        public readonly Stmt $stmt,
    ) {}
}
