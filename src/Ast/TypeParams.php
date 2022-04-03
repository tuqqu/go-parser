<?php

declare(strict_types=1);

namespace GoParser\Ast;

final class TypeParams implements AstNode
{
    public function __construct(
        public readonly Punctuation $lBracket,
        public readonly array $typeParamList,
        public readonly Punctuation $rBracket,
    ) {}
}
