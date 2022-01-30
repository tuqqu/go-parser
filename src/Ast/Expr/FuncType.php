<?php

declare(strict_types=1);

namespace GoParser\Ast\Expr;

use GoParser\Ast\Keyword;
use GoParser\Ast\Signature;

final class FuncType implements TypeLit
{
    public function __construct(
        public readonly Keyword $keyword,
        public readonly Signature $signature,
    ) {}
}
