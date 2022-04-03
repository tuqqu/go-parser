<?php

declare(strict_types=1);

namespace GoParser\Ast;

use GoParser\Ast\Expr\Ident;

final class MethodElem implements AstNode
{
    public function __construct(
        public readonly Ident $methodName,
        public readonly Signature $signature,
    ) {}
}
