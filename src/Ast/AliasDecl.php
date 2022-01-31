<?php

declare(strict_types=1);

namespace GoParser\Ast;

use GoParser\Ast\Expr\Ident;
use GoParser\Ast\Expr\Type;

final class AliasDecl
{
    public function __construct(
        public readonly Ident $ident,
        public readonly Operator $eq,
        public readonly Type $type,
    ) {}
}
