<?php

declare(strict_types=1);

namespace GoParser\Ast;

final class DefaultCase implements CaseLabel
{
    public function __construct(
        public readonly Keyword $keyword,
        public readonly Punctuation $colon,
    ) {}
}
