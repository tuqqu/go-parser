<?php

declare(strict_types=1);

namespace GoParser\Ast\Expr;

use GoParser\Ast\Keyword;
use GoParser\Ast\Punctuation;

/**
 * Parenthesised type "(" Type ")", e.g. (int)
 * It is not a TypeLit, but rather a generic Type.
 */
final class ParenType implements Type
{
    public function __construct(
        public readonly Punctuation $lParen,
        public readonly Type $type,
        public readonly Punctuation $rParen,
    ) {}
}
