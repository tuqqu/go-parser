<?php

declare(strict_types=1);

namespace GoParser\Ast\Expr;

use GoParser\Ast\Punctuation;

final class SliceType implements TypeLit
{
    public function __construct(
        public readonly Punctuation $lBrack,
        public readonly Punctuation $rBrack,
        public readonly Type $elemType,
    ) {}
}
