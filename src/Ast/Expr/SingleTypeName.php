<?php

declare(strict_types=1);

namespace GoParser\Ast\Expr;

use GoParser\Ast\TypeList;

final class SingleTypeName implements TypeName
{
    /**
     * @param TypeList[] $typeArgs
     */
    public function __construct(
        public readonly Ident $name,
        public readonly ?array $typeArgs,
    ) {}
}
