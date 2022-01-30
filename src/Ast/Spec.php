<?php

declare(strict_types=1);

namespace GoParser\Ast;

interface Spec extends AstNode
{
    public function isGroup(): bool;

    public function type(): SpecType;
}
