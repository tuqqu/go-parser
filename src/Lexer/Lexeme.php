<?php

declare(strict_types=1);

namespace GoParser\Lexer;

final class Lexeme
{
    public function __construct(
        public readonly Token $token,
        public readonly Position $pos,
        public readonly ?string $literal,
    ) {}

    public function __toString(): string
    {
        $str = \sprintf('%s %s', $this->pos, $this->token->name);
        if ($this->token->isLiteral()) {
            $str .= \sprintf(' "%s"', $this->literal ?? '');
        } elseif ($this->token->isOperator()) {
            $str .= \sprintf(' %s', $this->token->value);
        }

        return $str;
    }
}
