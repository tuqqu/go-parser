<?php

declare(strict_types=1);

namespace GoParser\Lexer;

enum Token: string
{
    // math operators
    case Plus = '+';
    case Minus = '-';
    case Mul = '*';
    case Div = '/';
    case Mod = '%';

    // compound math
    case PlusEq = '+=';
    case MinusEq = '-=';
    case MulEq = '*=';
    case DivEq = '/=';
    case ModEq = '%=';

    // bitwise operators
    case BitAnd = '&';
    case BitOr = '|';
    case BitXor = '^';
    case BitAndNot = '&^';
    case LeftShift = '<<';
    case RightShift = '>>';

    // compound bitwise
    case BitAndEq = '&=';
    case BitOrEq = '|=';
    case BitXorEq = '^=';
    case BitAndNotEq = '&^=';
    case LeftShiftEq = '<<=';
    case RightShiftEq = '>>=';

    // logic operators
    case LogicAnd = '&&';
    case LogicOr = '||';
    case LogicNot = '!';

    // eq & compare operators
    case EqEq = '==';
    case NotEq = '!=';
    case Less = '<';
    case LessEq = '<=';
    case Greater = '>';
    case GreaterEq = '>=';

    // other assignment operators
    case Eq = '=';
    case ColonEq = ':=';
    case Inc = '++';
    case Dec = '--';

    // misc operators
    case Arrow = '<-';
    case Ellipsis = '...';
    case LeftParen = '(';
    case RightParen = ')';
    case LeftBracket = '[';
    case RightBracket = ']';
    case LeftBrace = '{';
    case RightBrace = '}';
    case Semicolon = ';';
    case Colon = ':';
    case Comma = ',';
    case Dot = '.';

    // keywords
    case Break = 'break';
    case Case = 'case';
    case Chan = 'chan';
    case Const = 'const';
    case Continue = 'continue';
    case Default = 'default';
    case Defer = 'defer';
    case Else = 'else';
    case Fallthrough = 'fallthrough';
    case For = 'for';
    case Func = 'func';
    case Go = 'go';
    case Goto = 'goto';
    case If = 'if';
    case Import = 'import';
    case Interface = 'interface';
    case Map = 'map';
    case Package = 'package';
    case Range = 'range';
    case Return = 'return';
    case Select = 'select';
    case Struct = 'struct';
    case Switch = 'switch';
    case Type = 'type';
    case Var = 'var';

    // literals
    case Int = 'integer';
    case Float = 'floating_point';
    case Imag = 'imaginary';
    case Rune = 'char';
    case String = 'string';
    case RawString = 'raw_string';
    case Ident = 'identifier';

    // misc
    case Comment = 'comment';
    case MultilineComment = 'multiline_comment';
    case Illegal = 'illegal';
    case Eof = 'eof';

    public static function tryFromKeyword(string $from): ?self
    {
        $token = self::tryFrom($from);

        return $token?->isKeyword() ? $token : null;
    }

    public function isOperator(): bool
    {
        return match ($this) {
            self::Plus,
            self::Minus,
            self::Mul,
            self::Div,
            self::Mod,
            self::PlusEq,
            self::MinusEq,
            self::MulEq,
            self::DivEq,
            self::ModEq,
            self::BitAnd,
            self::BitOr,
            self::BitXor,
            self::BitAndNot,
            self::LeftShift,
            self::RightShift,
            self::BitAndEq,
            self::BitOrEq,
            self::BitXorEq,
            self::BitAndNotEq,
            self::LeftShiftEq,
            self::RightShiftEq,
            self::LogicAnd,
            self::LogicOr,
            self::LogicNot,
            self::EqEq,
            self::NotEq,
            self::Less,
            self::LessEq,
            self::Greater,
            self::GreaterEq,
            self::Eq,
            self::ColonEq,
            self::Inc,
            self::Dec,
            self::Arrow,
            self::Ellipsis,
            self::LeftParen,
            self::RightParen,
            self::LeftBracket,
            self::RightBracket,
            self::LeftBrace,
            self::RightBrace,
            self::Semicolon,
            self::Colon,
            self::Comma,
            self::Dot => true,
            default => false,
        };
    }

    public function isLiteral(): bool
    {
        return match ($this) {
            self::Int,
            self::Float,
            self::Imag,
            self::Ident,
            self::RawString,
            self::String => true,
            default => false,
        };
    }

    public function isKeyword(): bool
    {
        return match ($this) {
            self::Break,
            self::Case,
            self::Chan,
            self::Const,
            self::Continue,
            self::Default,
            self::Defer,
            self::Else,
            self::Fallthrough,
            self::For,
            self::Func,
            self::Go,
            self::Goto,
            self::If,
            self::Import,
            self::Interface,
            self::Map,
            self::Package,
            self::Range,
            self::Return,
            self::Select,
            self::Struct,
            self::Switch,
            self::Type,
            self::Var => true,
            default => false,
        };
    }
}
