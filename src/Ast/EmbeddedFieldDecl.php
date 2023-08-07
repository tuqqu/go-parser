<?php

declare(strict_types=1);

namespace GoParser\Ast;

use GoParser\Ast\Expr\PointerType;
use GoParser\Ast\Expr\RawStringLit;
use GoParser\Ast\Expr\StringLit;
use GoParser\Ast\Expr\TypeName;

final class EmbeddedFieldDecl implements AstNode
{
    public function __construct(
        public readonly TypeName|PointerType $type,
        public readonly StringLit|RawStringLit|null $tag,
    ) {}
}
