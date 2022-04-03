<?php

declare(strict_types=1);

namespace GoParser\Ast\Expr;

use GoParser\Ast\Keyword;
use GoParser\Ast\Punctuation;
use GoParser\Ast\Signature;

final class InterfaceType implements TypeLit
{
    /**
     * @param array<array{Ident, Signature}|TypeTerm> $items
     */
    public function __construct(
        public readonly Keyword $keyword,
        public readonly Punctuation $lBrace,
        public readonly array $items,
        public readonly Punctuation $rBrace,
    ) {}
}
