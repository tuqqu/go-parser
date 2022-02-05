<?php

declare(strict_types=1);

namespace GoParser\Ast;

use GoParser\Ast\Expr\Expr;
use GoParser\Ast\Expr\RawStringLit;
use GoParser\Ast\Expr\StringLit;

final class FieldDecl implements AstNode
{
    public function __construct(
        public readonly ?IdentList $identList,
        public readonly ?Expr $type,
        public readonly StringLit|RawStringLit|null $tag,
    ) {}
}
