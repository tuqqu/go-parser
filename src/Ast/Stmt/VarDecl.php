<?php

declare(strict_types=1);

namespace GoParser\Ast\Stmt;

use GoParser\Ast\Exception\InvalidArgument;
use GoParser\Ast\GroupSpec;
use GoParser\Ast\Keyword;
use GoParser\Ast\VarSpec;

final class VarDecl implements Decl
{
    public function __construct(
        public readonly Keyword $keyword,
        public readonly GroupSpec|VarSpec $spec,
    ) {
        if ($spec->type() !== SpecType::Var) {
            throw new InvalidArgument(\sprintf('Cannot create a VarDecl with Spec of type %s', $spec->type()->name));
        }
    }
}
