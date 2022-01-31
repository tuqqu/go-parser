<?php

declare(strict_types=1);

namespace GoParser\Ast;

use GoParser\Ast\Exception\InvalidArgument;
use GoParser\Ast\Expr\Ident;
use GoParser\Ast\Expr\Type;
use GoParser\Ast\Expr\TypeName;

final class IdentList implements AstNode
{
    /**
     * @param Ident[] $identifiers
     */
    public function __construct(
        public readonly array $identifiers,
    ) {
    }

    public static function fromExprList(ExprList $list): self
    {
        foreach ($list->exprs as $expr) {
            if (!$expr instanceof Ident) {
                throw new InvalidArgument('Cannot create IdentList from an arbitrary expression list');
            }
        }

        return new self($list->exprs);
    }

    public static function fromTypeList(TypeList $list): self
    {
        return new self(\array_map(
            static fn (Type $type): Ident =>
                $type instanceof TypeName ?
                    new Ident($type->pos, $type->name) :
                    throw new InvalidArgument('Cannot create IdentList from an arbitrary type list'),
            $list->types
        ));
    }
}
