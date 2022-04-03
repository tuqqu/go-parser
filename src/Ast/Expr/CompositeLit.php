<?php

declare(strict_types=1);

namespace GoParser\Ast\Expr;

use GoParser\Ast\ElementList;
use GoParser\Ast\Punctuation;

final class CompositeLit implements Literal
{
    public function __construct(
        public readonly ?Type $type,
        public readonly Punctuation $lBrace,
        public readonly ?ElementList $elementList,
        public readonly Punctuation $rBrace,
    ) {}
}
