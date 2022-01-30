<?php

declare(strict_types=1);

namespace GoParser\Ast\Expr;

use GoParser\Ast\Punctuation;

final class PointerType implements TypeLit
{
    public function __construct(
        public readonly Punctuation $op,
        public readonly Type $type,
    ) {}
}
