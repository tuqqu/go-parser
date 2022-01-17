<?php

declare(strict_types=1);

namespace GoParser\Lexer;

use GoParser\Lexer\Error\{LexError, UnclosedComment, UnexpectedCharacter, UnterminatedString};

final class Lexer
{
    private readonly string $src;
    private readonly int $len;
    private readonly ?string $filename;

    private int $offset = 0;
    private int $line = 1;
    private int $cur = 0;

    /** @var LexError[] */
    private array $errs = [];
    /** @var Lexeme[] */
    private array $lexemes = [];

    public function __construct(string $src, ?string $filename = null)
    {
        $this->src = $src;
        $this->len = \strlen($src);
        $this->filename = $filename;
    }

    public function getLexemes(): array
    {
        return $this->lexemes;
    }

    public function hasErrors(): bool
    {
        return \count($this->errs) !== 0;
    }

    public function getErrors(): array
    {
        return $this->errs;
    }

    public function lex(): void
    {
        while (true) {
            $this->skipWhitespace();

            switch ($this->peek()) {
                case "\n":
                    $this->read();
                    if ($this->isAutoSemicolon()) {
                        $this->addLexeme(Token::Semicolon);
                    }
                    break;
                case '"':
                    $this->string();
                    break;
                case '\'':
                    $this->rune();
                    break;
                case '`':
                    $this->rawString();
                    break;
                case ',':
                    $this->read();
                    $this->addLexeme(Token::Comma);
                    break;
                case '(':
                    $this->read();
                    $this->addLexeme(Token::LeftParen);
                    break;
                case ')':
                    $this->read();
                    $this->addLexeme(Token::RightParen);
                    break;
                case '[':
                    $this->read();
                    $this->addLexeme(Token::LeftBracket);
                    break;
                case ']':
                    $this->read();
                    $this->addLexeme(Token::RightBracket);
                    break;
                case '{':
                    $this->read();
                    $this->addLexeme(Token::LeftBrace);
                    break;
                case '}':
                    $this->read();
                    $this->addLexeme(Token::RightBrace);
                    break;
                case ';':
                    $this->read();
                    $this->addLexeme(Token::Semicolon);
                    break;
                case ':':
                    $this->ifEqElse(Token::ColonEq, Token::Colon);
                    break;
                case '.':
                    $next = $this->peekNext();
                    switch (true) {
                        case self::isNumeric($next):
                            $this->number();
                            break;
                        case $next === '.':
                            $this->ellipsis();
                            break;
                        default:
                            $this->read();
                            $this->addLexeme(Token::Dot);
                    }
                    break;

                case '+':
                    $this->read();
                    switch ($this->peek()) {
                        case '=':
                            $this->read();
                            $this->addLexeme(Token::PlusEq);
                            break;
                        case '+':
                            $this->read();
                            $this->addLexeme(Token::Inc);
                            break;
                        default:
                            $this->addLexeme(Token::Plus);
                    }
                    break;
                case '-':
                    $this->read();
                    switch ($this->peek()) {
                        case '=':
                            $this->read();
                            $this->addLexeme(Token::MinusEq);
                            break;
                        case '-':
                            $this->addLexeme(Token::Dec);
                            break;
                        default:
                            $this->addLexeme(Token::Minus);
                    }
                    break;
                case '*':
                    $this->ifEqElse(Token::MulEq, Token::Mul);
                    break;
                case '%':
                    $this->ifEqElse(Token::ModEq, Token::Mod);
                    break;
                case '^':
                    $this->ifEqElse(Token::BitXorEq, Token::BitXor);
                    break;
                case '=':
                    $this->ifEqElse(Token::EqEq, Token::Eq);
                    break;
                case '!':
                    $this->ifEqElse(Token::NotEq, Token::LogicNot);
                    break;
                case '&':
                    $this->read();
                    switch ($this->peek()) {
                        case '&':
                            $this->read();
                            $this->addLexeme(Token::LogicAnd);
                            break;
                        case '=':
                            $this->read();
                            $this->addLexeme(Token::BitAndEq);
                            break;
                        case '^':
                            $this->ifEqElse(Token::BitAndNotEq, Token::BitAndNot);
                            break;
                        default:
                            $this->read();
                            $this->addLexeme(Token::BitAnd);
                    }
                    break;
                case '|':
                    $this->read();
                    switch ($this->peek()) {
                        case '|':
                            $this->read();
                            $this->addLexeme(Token::LogicOr);
                            break;
                        case '=':
                            $this->read();
                            $this->addLexeme(Token::BitOrEq);
                            break;
                        default:
                            $this->read();
                            $this->addLexeme(Token::BitOr);
                    }
                    break;
                case '>':
                    $this->read();
                    if ($this->peek() === '=') {
                        $this->read();
                        $this->addLexeme(Token::GreaterEq);
                    } else {
                        $this->read();
                        if ($this->peek() === '>') {
                            $this->ifEqElse(Token::RightShiftEq, Token::RightShift);
                        } else {
                            $this->addLexeme(Token::Greater);
                        }
                    }
                    break;
                case '<':
                    if ($this->peekNext() === '-') {
                        $this->read();
                        $this->read();
                        $this->addLexeme(Token::Arrow);
                    } else {
                        $this->read();
                        if ($this->peek() === '=') {
                            $this->read();
                            $this->addLexeme(Token::LessEq);
                        } else {
                            $this->read();
                            if ($this->peek() === '<') {
                                $this->ifEqElse(Token::LeftShiftEq, Token::LeftShift);
                            } else {
                                $this->addLexeme(Token::Less);
                            }
                        }
                    }
                    break;
                case '/':
                    $next = $this->peekNext();
                    if ($next === '/' || $next === '*') {
                        $this->comment();
                    } else {
                        $this->read();
                        if ($this->peek() === '=') {
                            $this->read();
                            $this->addLexeme(Token::DivEq);
                        } else {
                            $this->addLexeme(Token::Div);
                        }
                    }
                    break;
                default:
                    $char = $this->peek();
                    switch (true) {
                        case $char === null:
                            if ($this->isAutoSemicolon()) {
                                // todo consider autosemicolon token
                                $this->addLexeme(Token::Semicolon);
                            }
                            $this->addLexeme(Token::Eof);
                            return;
                        case self::isAlphabetic($char):
                            $this->identifier();
                            break;
                        case self::isNumeric($char):
                            $this->number();
                            break;
                    }

            }
        }
    }

    private function ifEqElse(Token $if, Token $else): void
    {
        $this->read();
        if ($this->peek() === '=') {
            $this->read();
            $this->addLexeme($if);
        } else {
            $this->addLexeme($else);
        }
    }

    private function comment(): void
    {
        // first slash
        $this->read();
        $char = $this->read();
        $comment = '';

        switch ($char) {
            case '/':
                while (($char = $this->read()) !== "\n") {
                    $comment .= $char;
                    if ($this->isAtEnd()) {
                        break;
                    }
                }
                $this->addLexeme(Token::Comment, $comment);
                break;
            case '*':
                while (true) {
                    $char = $this->read();
                    if ($char === '*' && $this->peek() === '/') {
                        $this->read();
                        break;
                    }

                    $comment .= $char;
                    if ($this->isAtEnd()) {
                        $this->addError(new UnclosedComment());
                    }
                }
                $this->addLexeme(Token::MultilineComment, $comment);
                break;
            default:
                // unreachable
        }
    }

    private function skipWhitespace(): void
    {
        while (match ($this->peek()) {
            ' ', "\t", "\r" => true,
            default => false,
        }) {
            $this->read();
        }
    }

    private function string(): void
    {
        $str = $this->quotedLiteral('"');
        $this->addLexeme(Token::String, $str);
    }

    private function rawString(): void
    {
        $str = $this->quotedLiteral('`');
        $this->addLexeme(Token::RawString, $str);
    }

    private function rune(): void
    {
        $char = $this->quotedLiteral('\'');
        $this->addLexeme(Token::Rune, $char);
    }

    private function quotedLiteral(string $quote): string
    {
        $literal = $this->read();

        while (true) {
            switch ($this->peek()) {
                case $quote:
                    $this->read();
                    $literal .= $quote;
                    break 2;
                case "\n":
                case null:
                    $this->addError(new UnterminatedString());
                default:
                    $literal .= $this->read();
            }
        }

        return $literal;
    }

    private function ellipsis(): void
    {
        for ($i = 0; $i < 3; ++$i) {
            $dot = $this->read();
            if ($dot !== '.') {
                $this->addError(new UnexpectedCharacter());
            }
        }

        $this->addLexeme(Token::Ellipsis);
    }

    private function number(): void
    {
        $literal = '';
        $char = $this->peek();

        if ($char === '.') {
            $literal .= $this->fraction();
            $token = Token::Float;
        } elseif ($char === '0') {
            $literal .= $this->read();
            switch ($char = $this->peek()) {
                // hex
                case 'x':
                case 'X':
                    $literal .= $this->read();
                    $literal .= $this->digits(self::isHex(...));
                    $token = Token::Int;
                    break;
                // binary
                case 'b':
                case 'B':
                    $literal .= $this->read();
                    $literal .= $this->digits(self::isBinary(...));
                    $token = Token::Int;
                    break;
                // octal
                case 'o':
                case 'O':
                    $literal .= $this->read();
                    $literal .= $this->digits(self::isOctal(...));
                    $token = Token::Int;
                    break;
                default:
                    // old octal notation
                    if (self::isNumeric($this->peek())) {
                        $literal .= $this->digits(self::isOctal(...));
                        $token = Token::Int;
                    } else {
                        [$num, $token] = $this->decimalFloat();
                        $literal .= $num;
                    }
            }
        } else {
            [$num, $token] = $this->decimalFloat();
            $literal .= $num;
        }

        // imaginary
        if ($this->peek() === 'i') {
            $literal .= $this->read();
            $token = Token::ComplexImag;
        }

        $this->addLexeme($token, $literal);
    }

    private function decimalFloat(): array
    {
        $literal = $this->digits(self::isNumeric(...));
        $token = Token::Int;

        switch ($this->peek()) {
            case 'e':
            case 'E':
                $literal .= $this->exponent();
                break;
            case '.':
                $literal .= $this->fraction();
                $token = Token::Float;
        }

        return [$literal, $token];
    }

    private function fraction(): string
    {
        $fraction = $this->read();
        $fraction .= $this->digits(self::isNumeric(...), false);

        if ($this->match('e', 'E')) {
            $fraction .= $this->exponent();
        }

        return $fraction;
    }

    private function exponent(): string
    {
        $literal = $this->read();
        if ($this->match('+', '-')) {
            $literal .= $this->read();
        }

        $literal .= $this->digits(self::isNumeric(...), false);

        return $literal;
    }

    private function digits(callable $validate, bool $separatorPrefix = true): string
    {
        $digits = '';
        $sep = false;

        if (!$separatorPrefix && $this->peek() === '_') {
            // todo '\'_\' must separate successive digits'
            $this->addError(new UnexpectedCharacter());
        }

        while (($char = $this->peek()) !== null) {
            switch (true) {
                case $char === '_':
                    if (!$sep) {
                        $digits .= $this->read();
                        $sep = true;
                    } else {
                        // todo two __
                        $this->addError(new UnexpectedCharacter());
                    }
                    break;
                case self::isAlphanumeric($char):
                    if ($validate($char)) {
                        $digits .= $this->read();
                        $sep = false;
                    } else {
                        // todo integer notation error
                        dump('integer notation error');
                        $this->addError(new UnexpectedCharacter());
                    }
                    break;
                default:
                    break 2;
            }
        }

        if ($sep) {
            // todo separator cant be last
            $this->addError(new UnexpectedCharacter());
        }

        return $digits;
    }

    private function identifier(): void
    {
        $ident = '';

        while ($char = $this->peek()) {
            if (self::isAlphanumeric($char)) {
                $ident .= $this->read();
            } else {
                break;
            }
        }

        $token = Token::tryFrom($ident);

        if ($token === null) {
            $this->addLexeme(Token::Ident, $ident);
            return;
        }

        $this->addLexeme($token);
    }

    private function isAutoSemicolon(): bool
    {
        $len = \count($this->lexemes);

        if ($len === 0) {
            return false;
        }

        return match($this->lexemes[$len - 1]->token) {
            Token::Ident,
            Token::Int,
            Token::Float,
            Token::ComplexImag,
            Token::Rune,
            Token::String,
            Token::Break,
            Token::Continue,
            Token::Fallthrough,
            Token::Return,
            Token::Inc,
            Token::Dec,
            Token::RightBrace,
            Token::RightBracket,
            Token::RightParen => true,
            default => false,
        };
    }

    private function pos(): Position
    {
        return new Position($this->offset, $this->line, $this->filename);
    }

    private static function isAlphabetic(string $char): bool
    {
        return \ctype_alpha($char) || $char === '_';
    }

    private static function isAlphanumeric(string $char): bool
    {
        return \ctype_alnum($char) || $char === '_';
    }

    private static function isNumeric(string $char): bool
    {
        return \ctype_digit($char);
    }

    private static function isOctal(string $char): bool
    {
        return \decoct(\octdec($char)) === $char;
    }

    private static function isHex(string $char): bool
    {
        return \ctype_xdigit($char);
    }

    private static function isBinary(string $char): bool
    {
        return \in_array($char, ['0', '1']);
    }

    // src manipulation

    private function read(): string
    {
        $char = $this->advance();

        if ($char === null) {
            throw new \OutOfBoundsException(\sprintf('No char at index %d for src of length %d', $this->cur, $this->len));
        }

        if ($char === "\n") {
            $this->line++;
        }

        $this->offset++;

        return $char;
    }

    private function match(string ...$char): bool
    {
        return \in_array($this->peek(), $char, true);
    }

    private function peek(): ?string
    {
        return $this->src[$this->cur] ?? null;
    }

    private function peekNext(): ?string
    {
        return $this->src[$this->cur + 1] ?? null;
    }

    private function advance(): ?string
    {
        if ($this->cur >= $this->len) {
            return null;
        }

        return $this->src[$this->cur++];
    }

    private function isAtEnd(): bool
    {
         return $this->cur === $this->len;
    }

    private function addLexeme(Token $token, ?string $literal = null): void
    {
        $this->lexemes[] = new Lexeme($token, $this->pos(), $literal);
    }

    private function addError(LexError $err): never
    {
        $this->errs[] = $err;
        throw new $err;
    }
}
