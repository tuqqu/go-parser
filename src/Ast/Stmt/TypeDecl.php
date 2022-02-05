<?php

declare(strict_types=1);

namespace GoParser\Ast\Stmt;

use GoParser\Exception\InvalidArgument;
use GoParser\Ast\GroupSpec;
use GoParser\Ast\Keyword;
use GoParser\Ast\SpecType;
use GoParser\Ast\TypeSpec;

final class TypeDecl implements Decl
{
    public function __construct(
        public readonly Keyword $keyword,
        public readonly GroupSpec|TypeSpec $spec,
    ) {
        if ($spec->type() !== SpecType::Type) {
            throw new InvalidArgument(\sprintf('Cannot create a TypeDecl with Spec of type %s', $spec->type()->name));
        }
    }
}
