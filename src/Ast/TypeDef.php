<?php

declare(strict_types=1);

namespace GoParser\Ast;

use GoParser\Ast\Expr\Ident;
use GoParser\Ast\Expr\Type;

final class TypeDef
{
    public function __construct(
        public readonly Ident $ident,
        public readonly Type $type,
    ) {}
}
