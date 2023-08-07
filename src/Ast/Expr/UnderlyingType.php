<?php

declare(strict_types=1);

namespace GoParser\Ast\Expr;

use GoParser\Ast\Operator;

final class UnderlyingType implements TypeTerm, Expr
{
    public function __construct(
        public readonly Operator $op,
        public readonly Type $type,
    ) {}
}
