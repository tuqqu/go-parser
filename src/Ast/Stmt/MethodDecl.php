<?php

declare(strict_types=1);

namespace GoParser\Ast\Stmt;

use GoParser\Ast\Expr\Ident;
use GoParser\Ast\Keyword;
use GoParser\Ast\Params;
use GoParser\Ast\Signature;

final class MethodDecl implements Decl
{
    public function __construct(
        public readonly Keyword $keyword,
        public readonly Params $receiver,
        public readonly Ident $name,
        public readonly Signature $sign,
        public readonly ?BlockStmt $body,
    ) {}
}
