<?php

declare(strict_types=1);

namespace GoParser\Ast;

use GoParser\Ast\Expr\Type;

final class TypeList
{
    /**
     * @param Type[] $types
     */
    public function __construct(
        public readonly array $types,
    ) {}
}
