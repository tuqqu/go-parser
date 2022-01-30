<?php

declare(strict_types=1);

namespace GoParser\Ast;

final class File implements AstNode
{
    public function __construct(
        public readonly PackageClause $package,
        public readonly array $imports,
        public readonly array $decls,
    ) {}
}
