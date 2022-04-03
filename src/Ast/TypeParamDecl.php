<?php

declare(strict_types=1);

namespace GoParser\Ast;

use GoParser\Ast\Expr\TypeElem;

final class TypeParamDecl implements AstNode
{
    public function __construct(
        public readonly IdentList $identList,
        public readonly TypeElem $type,
    ) {}
}
