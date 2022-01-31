<?php

declare(strict_types=1);

namespace GoParser\Ast;

final class TypeSpec implements Spec
{
    public function __construct(
        public readonly AliasDecl|TypeDef $value,
    ) {}

    public function isGroup(): bool
    {
        return false;
    }

    public function type(): SpecType
    {
        return SpecType::Type;
    }
}
