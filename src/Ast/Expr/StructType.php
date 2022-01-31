<?php

declare(strict_types=1);

namespace GoParser\Ast\Expr;

use GoParser\Ast\FieldDecl;
use GoParser\Ast\Keyword;
use GoParser\Ast\Punctuation;

final class StructType implements TypeLit
{
    /**
     * @param FieldDecl[] $fieldDecls
     */
    public function __construct(
        public readonly Keyword $keyword,
        public readonly Punctuation $lBrace,
        public readonly array $fieldDecls,
        public readonly Punctuation $rBrace,
    ) {}
}
