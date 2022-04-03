<?php

declare(strict_types=1);

namespace GoParser\Ast\Expr;

final class TypeElem implements TypeTerm, Expr
{
    /**
     * @param TypeTerm[] $typeTerms
     */
    public function __construct(
        public readonly array $typeTerms,
    ) {}
}
