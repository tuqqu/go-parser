<?php

declare(strict_types=1);

namespace GoParser\Ast\Stmt;

use GoParser\Exception\InvalidArgument;
use GoParser\Ast\GroupSpec;
use GoParser\Ast\ImportSpec;
use GoParser\Ast\Keyword;
use GoParser\Ast\SpecType;

final class ImportDecl implements Decl
{
    public function __construct(
        public readonly Keyword $keyword,
        public readonly GroupSpec|ImportSpec $spec,
    ) {
        if ($spec->type() !== SpecType::Import) {
            throw new InvalidArgument(\sprintf('Cannot create a ImportDecl with Spec of type %s', $spec->type()->name));
        }
    }
}
