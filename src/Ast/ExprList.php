<?php

declare(strict_types=1);

namespace GoParser\Ast;

final class ExprList
{
    public function __construct(
        public readonly array $exprs,
    ) {}
}
