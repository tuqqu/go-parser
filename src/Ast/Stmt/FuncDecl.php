<?php

declare(strict_types=1);

namespace GoParser\Ast\Stmt;

use GoParser\Ast\Expr\Ident;
use GoParser\Ast\Keyword;
use GoParser\Ast\Signature;

final class FuncDecl implements Decl
{
    public function __construct(
        public readonly Keyword $keyword,
        public readonly Ident $name,
        public readonly Signature $signature,
        public readonly ?BlockStmt $body,
    ) {}
}
