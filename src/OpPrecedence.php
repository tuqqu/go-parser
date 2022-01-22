<?php

declare(strict_types=1);

namespace GoParser;

use GoParser\Lexer\Token;

enum OpPrecedence: int
{
    case None = 0;
    case Or = 1;
    case And = 2;
    case Equality = 3;
    case Term = 4;
    case Factor = 5;
    case Unary = 6;

    public static function fromToken(Token $token): self
    {
        return match ($token) {
            Token::LogicOr => self::Or,
            Token::LogicAnd => self::And,
            Token::EqEq,
            Token::NotEq,
            Token::Less,
            Token::LessEq,
            Token::Greater,
            Token::GreaterEq => self::Equality,
            Token::Plus,
            Token::Minus,
            Token::BitOr,
            Token::BitXor => self::Term,
            Token::Mul,
            Token::Div,
            Token::LeftShift,
            Token::RightShift,
            Token::BitAnd,
            Token::BitAndNot => self::Factor,
            default => self::None,
        };
    }
}
