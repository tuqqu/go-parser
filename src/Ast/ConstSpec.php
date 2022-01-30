<?php

declare(strict_types=1);

namespace GoParser\Ast;

use GoParser\Ast\Expr\Type;

final class ConstSpec implements Spec
{
    public function __construct(
        public readonly IdentList $identList,
        public readonly ?Type $type,
        public readonly ExprList $initList,
    ) {}

    public function isGroup(): bool
    {
        return false;
    }

    public function type(): SpecType
    {
        return SpecType::Const;
    }
}
