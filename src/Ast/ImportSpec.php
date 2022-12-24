<?php

declare(strict_types=1);

namespace GoParser\Ast;

use GoParser\Ast\Expr\Ident;
use GoParser\Ast\Expr\RawStringLit;
use GoParser\Ast\Expr\StringLit;

final class ImportSpec implements Spec
{
    public function __construct(
        public readonly Ident|Punctuation|null $name,
        public readonly StringLit|RawStringLit $path,
    ) {}

    public function isGroup(): bool
    {
        return false;
    }

    public function type(): SpecType
    {
        return SpecType::Import;
    }
}
