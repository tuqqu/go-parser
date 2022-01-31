<?php

declare(strict_types=1);

namespace GoParser\Ast;

enum SpecType
{
    case Import;
    case Var;
    case Const;
    case Type;
}
