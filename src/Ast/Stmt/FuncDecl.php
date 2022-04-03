<?php

declare(strict_types=1);

namespace GoParser\Ast\Stmt;

use GoParser\Ast\Expr\Ident;
use GoParser\Ast\Keyword;
use GoParser\Ast\Signature;
use GoParser\Ast\TypeParams;

final class FuncDecl implements Decl
{
    public function __construct(
        public readonly Keyword $keyword,
        public readonly Ident $name,
        public readonly ?TypeParams $typeParams,
        public readonly Signature $signature,
        public readonly ?BlockStmt $body,
    ) {}
}
