<?php

declare(strict_types=1);

namespace GoParser\Ast\Stmt;

use GoParser\Lexer\Position;

final class EmptyStmt implements SimpleStmt
{
    public function __construct(
        public readonly Position $pos,
    ) {}
}
