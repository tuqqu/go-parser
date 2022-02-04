<?php

declare(strict_types=1);

namespace GoParser\Ast\Expr;

final class QualifiedTypeName implements TypeName
{
    public function __construct(
        public readonly Ident $packageName,
        public readonly SingleTypeName $typeName,
    ) {}
}
