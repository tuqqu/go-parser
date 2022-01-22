<?php

declare(strict_types=1);

namespace GoParser\Lexer\Error;

use GoParser\Error;

abstract class LexError extends \Exception implements Error
{
}
