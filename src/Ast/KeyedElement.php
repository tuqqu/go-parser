<?php

declare(strict_types=1);

namespace GoParser\Ast;

use GoParser\Ast\Exception\InvalidArgument;
use GoParser\Ast\Expr\Expr;

final class KeyedElement
{
    public function __construct(
        public readonly ?Expr $key,
        public readonly ?Punctuation $colon,
        public readonly Expr $element,
    ) {
        if ((bool) $key !== (bool) $colon) {
            throw new InvalidArgument('KeyedElement must have both key and colon or neither.');
        }
    }
}
