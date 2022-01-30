<?php

declare(strict_types=1);

namespace GoParser\Ast\Expr;

use GoParser\Ast\Punctuation;

final class ArrayType implements TypeLit
{
    public function __construct(
        public readonly Punctuation $lBrack,
        public readonly Expr|Punctuation $len,
        public readonly Punctuation $rBrack,
        public readonly Type $elemType,
    ) {}
}
