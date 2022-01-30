<?php

declare(strict_types=1);

namespace GoParser\Ast;

use GoParser\Ast\Exception\InvalidArgument;
use GoParser\Ast\Expr\Ident;

final class IdentList
{
    /**
     * @param Ident[] $identifiers
     */
    public function __construct(
        public readonly array $identifiers,
    ) {}

    public static function fromExprList(ExprList $list): self
    {
        foreach ($list->exprs as $expr) {
            if (!$expr instanceof Ident) {
                throw new InvalidArgument('Cannot create IdentList from an arbitrary expression list');
            }
        }

        return new self($list->exprs);
    }
}
