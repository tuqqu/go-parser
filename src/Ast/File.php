<?php

declare(strict_types=1);

namespace GoParser\Ast;

use GoParser\Ast\Stmt\Decl;
use GoParser\Ast\Stmt\ImportDecl;

final class File implements AstNode
{
    /**
     * @param ImportDecl[]|GroupSpec[] $imports
     * @param Decl[]|GroupSpec[] $decls
     */
    public function __construct(
        public readonly PackageClause $package,
        public readonly array $imports,
        public readonly array $decls,
        public readonly ?string $filename,
    ) {}
}
