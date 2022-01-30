<?php

declare(strict_types=1);

namespace GoParser\Ast\Expr;

use GoParser\Ast\Keyword;
use GoParser\Ast\Punctuation;

final class MapType implements TypeLit
{
    public function __construct(
        public readonly Keyword $keyword,
        public readonly Punctuation $lBrack,
        public readonly Type $keyType,
        public readonly Punctuation $rBrack,
        public readonly Type $elemType,
    ) {}
}
