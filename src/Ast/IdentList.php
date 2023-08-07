<?php

declare(strict_types=1);

namespace GoParser\Ast;

use GoParser\Ast\Expr\Type;
use GoParser\Exception\InvalidArgument;
use GoParser\Ast\Expr\Expr;
use GoParser\Ast\Expr\Ident;
use GoParser\Ast\Expr\SingleTypeName;

use function array_map;

final class IdentList implements AstNode
{
    /**
     * @param Ident[] $idents
     */
    public function __construct(
        public readonly array $idents,
    ) {}

    public static function fromExprList(ExprList $list): self
    {
        return new self(array_map(
            static fn (Expr $expr): Ident => $expr instanceof Ident
                ? $expr
                : throw new InvalidArgument('Cannot create IdentList from an arbitrary expression list'),
            $list->exprs,
        ));
    }

    public static function fromTypeList(TypeList $list): self
    {
        return new self(array_map(
            static fn (Type $type): Ident => $type instanceof SingleTypeName
                ? $type->name
                : throw new InvalidArgument('Cannot create IdentList from an arbitrary type list'),
            $list->types,
        ));
    }
}
