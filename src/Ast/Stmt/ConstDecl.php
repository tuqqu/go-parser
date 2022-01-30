<?php

declare(strict_types=1);

namespace GoParser\Ast\Stmt;

use GoParser\Ast\ConstSpec;
use GoParser\Ast\Exception\InvalidArgument;
use GoParser\Ast\GroupSpec;
use GoParser\Ast\Keyword;
use GoParser\Ast\SpecType;

final class ConstDecl implements Decl
{
    public function __construct(
        public readonly Keyword $keyword,
        public readonly GroupSpec|ConstSpec $spec,
    ) {
        if ($spec->type() !== SpecType::Const) {
            throw new InvalidArgument(\sprintf('Cannot create a ConstDecl with Spec of type %s', $spec->type()->name));
        }
    }
}
