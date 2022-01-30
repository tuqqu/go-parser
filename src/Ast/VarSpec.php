<?php

declare(strict_types=1);

namespace GoParser\Ast;

use GoParser\Ast\Exception\InvalidArgument;
use GoParser\Ast\Expr\Type;

final class VarSpec implements Spec
{
    public function __construct(
        public readonly IdentList $identList,
        public readonly ?Type $type,
        public readonly ?ExprList $initList,
    ) {
        if ($type === null && $initList === null) {
            throw new InvalidArgument('Var must have either type or init value');
        }
    }

    public function isGroup(): bool
    {
        return false;
    }

    public function type(): SpecType
    {
        return SpecType::Var;
    }
}
