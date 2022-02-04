<?php

declare(strict_types=1);

namespace GoParser\Ast\Expr;

use GoParser\Ast\Keyword;
use GoParser\Ast\Punctuation;

/**
 * Represents a parenthesised type "(" Type ")", e.g. (int)
 * in both TypeAssertion and TypeSwitchGuard.
 *
 * In the latter case  $type is a Keyword "type".
 */
final class ParenType implements Type
{
    public function __construct(
        public readonly Punctuation $lParen,
        public readonly Type|Keyword $type,
        public readonly Punctuation $rParen,
    ) {}
}
