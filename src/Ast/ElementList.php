<?php

declare(strict_types=1);

namespace GoParser\Ast;

final class ElementList implements AstNode
{
    /**
     * @param KeyedElement[] $elements
     */
    public function __construct(
        public readonly array $elements,
    ) {}
}
