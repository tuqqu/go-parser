<?php

declare(strict_types=1);

namespace GoParser\Lexer;

use function sprintf;

final class Position
{
    public function __construct(
        public readonly int $offset,
        public readonly int $line,
        public readonly ?string $filename,
    ) {}

    public function __toString(): string
    {
        return sprintf(
            '%s:%d:%d',
            $this->filename === null ? '' : $this->filename . ':',
            $this->line,
            $this->offset
        );
    }
}
