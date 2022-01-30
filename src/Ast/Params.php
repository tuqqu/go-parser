<?php

declare(strict_types=1);

namespace GoParser\Ast;

final class Params implements AstNode
{
    /**
     * @param ParamDecl[] $paramList
     */
    public function __construct(
        public readonly Punctuation $lParen,
        public readonly array $paramList,
        public readonly Punctuation $rParen,
    ) {}
}
