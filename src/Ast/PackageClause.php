<?php

declare(strict_types=1);

namespace GoParser\Ast;

use GoParser\Ast\Expr\Ident;

final class PackageClause implements AstNode
{
    public function __construct(
        public readonly Keyword $keyword,
        public readonly Ident $identifier,
    ) {}
}
