<?php

declare(strict_types=1);

namespace GoParser\Ast;

use GoParser\Ast\Expr\Type;

final class ParamDecl implements AstNode
{
    public function __construct(
        public readonly ?IdentList $identList,
        public readonly ?Punctuation $ellipsis,
        public readonly Type $type,
    ) {}
}
