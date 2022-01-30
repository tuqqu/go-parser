<?php

declare(strict_types=1);

namespace GoParser\Ast;

final class TypeSwitchCase implements CaseLabel
{
    public function __construct(
        public readonly Keyword $keyword,
        public readonly TypeList $types,
        public readonly Punctuation $colon,
    ) {}
}
