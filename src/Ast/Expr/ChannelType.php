<?php

declare(strict_types=1);

namespace GoParser\Ast\Expr;

use GoParser\Ast\Keyword;
use GoParser\Ast\Operator;

final class ChannelType implements TypeLit
{
    /**
     * @param array<Keyword|Operator> $chan represents the "chan" | "chan" "<-" | "<-" "chan" part
     */
    public function __construct(
        public readonly array $chan,
        public readonly Type $elemType,
    ) {}
}
