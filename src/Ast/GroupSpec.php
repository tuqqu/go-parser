<?php

declare(strict_types=1);

namespace GoParser\Ast;

final class GroupSpec implements Spec
{
    /**
     * @param Spec[] $specs
     */
    public function __construct(
        public readonly Punctuation $leftParen,
        public readonly array $specs,
        public readonly Punctuation $rightParen,
        public readonly SpecType $specType,
    ) {}

    public function isGroup(): bool
    {
        return true;
    }

    public function type(): SpecType
    {
        return $this->specType;
    }
}
