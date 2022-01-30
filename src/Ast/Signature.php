<?php

declare(strict_types=1);

namespace GoParser\Ast;

use GoParser\Ast\Expr\Type;

final class Signature implements AstNode
{
    public function __construct(
        public readonly Params $params,
        public readonly Params|Type|null $result,
    ) {}
}
