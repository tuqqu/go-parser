<?php

declare(strict_types=1);

namespace GoParser\Ast;

enum SpecType implements \JsonSerializable
{
    case Import;
    case Var;
    case Const;
    case Type;

    public function jsonSerialize(): string
    {
        return $this->name;
    }
}
