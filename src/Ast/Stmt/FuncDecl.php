<?php

declare(strict_types=1);

namespace GoParser\Ast\Stmt;

use GoParser\Ast\Expr\Ident;
use GoParser\Ast\Signature;
use GoParser\Ast\Keyword;

final class FuncDecl implements Decl
{
    public function __construct(
        public readonly Keyword $keyword,
        public readonly Ident $name,
        public readonly Signature $sign,
        public readonly ?BlockStmt $body,
    ) {}
}
