<?php

declare(strict_types=1);

namespace GoParser\Ast;

final class ExprSwitchCase implements CaseLabel
{
    public function __construct(
        public readonly Keyword $keyword,
        public readonly ExprList $exprList,
        public readonly Punctuation $colon,
    ) {}
}
