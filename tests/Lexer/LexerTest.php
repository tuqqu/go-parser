<?php

declare(strict_types=1);

namespace Tests\GoParser\Lexer;

use GoParser\Lexer\Lexer;
use PHPUnit\Framework\TestCase;

final class LexerTest extends TestCase
{
    public function testLex(): void
    {
        $go = <<<GO
        // test file
        package main
        
        import "fmt"
        
        func plusDiv(a, b, c int) int {
            return (a + b) / c
        }
        
        func f(from string) {
            for i := 0; i < 3; i++ {
                fmt.Println(from, ":", i)
            }
        }
        
        func main() {
            res := plusDiv(1, 9, 5)
            fmt.Println("(1+9)/5=", res)
        
            go f("goroutine")
        }
        GO;

        $tokens = <<<TOKENS
        [1, 12] Comment
        [2, 20] Package
        [2, 25] Ident "main"
        [3, 26] Semicolon ;
        [4, 33] Import
        [4, 39] String ""fmt""
        [5, 40] Semicolon ;
        [6, 45] Func
        [6, 53] Ident "plusDiv"
        [6, 54] LeftParen (
        [6, 55] Ident "a"
        [6, 56] Comma ,
        [6, 58] Ident "b"
        [6, 59] Comma ,
        [6, 61] Ident "c"
        [6, 65] Ident "int"
        [6, 66] RightParen )
        [6, 70] Ident "int"
        [6, 72] LeftBrace {
        [7, 83] Return
        [7, 85] LeftParen (
        [7, 86] Ident "a"
        [7, 88] Plus +
        [7, 90] Ident "b"
        [7, 91] RightParen )
        [7, 93] Div /
        [7, 95] Ident "c"
        [8, 96] Semicolon ;
        [8, 97] RightBrace }
        [9, 98] Semicolon ;
        [10, 103] Func
        [10, 105] Ident "f"
        [10, 106] LeftParen (
        [10, 110] Ident "from"
        [10, 117] Ident "string"
        [10, 118] RightParen )
        [10, 120] LeftBrace {
        [11, 128] For
        [11, 130] Ident "i"
        [11, 133] ColonEq :=
        [11, 135] Int "0"
        [11, 136] Semicolon ;
        [11, 138] Ident "i"
        [11, 141] Less <
        [11, 142] Int "3"
        [11, 143] Semicolon ;
        [11, 145] Ident "i"
        [11, 147] Inc ++
        [11, 149] LeftBrace {
        [12, 161] Ident "fmt"
        [12, 162] Dot .
        [12, 169] Ident "Println"
        [12, 170] LeftParen (
        [12, 174] Ident "from"
        [12, 175] Comma ,
        [12, 179] String "":""
        [12, 180] Comma ,
        [12, 182] Ident "i"
        [12, 183] RightParen )
        [13, 184] Semicolon ;
        [13, 189] RightBrace }
        [14, 190] Semicolon ;
        [14, 191] RightBrace }
        [15, 192] Semicolon ;
        [16, 197] Func
        [16, 202] Ident "main"
        [16, 203] LeftParen (
        [16, 204] RightParen )
        [16, 206] LeftBrace {
        [17, 214] Ident "res"
        [17, 217] ColonEq :=
        [17, 225] Ident "plusDiv"
        [17, 226] LeftParen (
        [17, 227] Int "1"
        [17, 228] Comma ,
        [17, 230] Int "9"
        [17, 231] Comma ,
        [17, 233] Int "5"
        [17, 234] RightParen )
        [18, 235] Semicolon ;
        [18, 242] Ident "fmt"
        [18, 243] Dot .
        [18, 250] Ident "Println"
        [18, 251] LeftParen (
        [18, 261] String ""(1+9)/5=""
        [18, 262] Comma ,
        [18, 266] Ident "res"
        [18, 267] RightParen )
        [19, 268] Semicolon ;
        [20, 275] Go
        [20, 277] Ident "f"
        [20, 278] LeftParen (
        [20, 289] String ""goroutine""
        [20, 290] RightParen )
        [21, 291] Semicolon ;
        [21, 292] RightBrace }
        [21, 292] Semicolon ;
        [21, 292] Eof
        TOKENS;

        $lexer = new Lexer($go);
        $lexer->lex();

        $lexemes = $lexer->getLexemes();
        $tokens = explode("\n", $tokens);

        foreach ($lexemes as $i => $lexeme) {
            self::assertEquals((string) $lexeme, $tokens[$i]);
        }

        $errs = $lexer->getErrors();

        self::assertCount(0, $errs);
    }

    public function testUnclosedCommentLex(): void
    {
        $lexer = new Lexer(<<<GO
        var x int
        /* comment
        GO);

        $lexer->lex();
        $errs = $lexer->getErrors();

        self::assertCount(1, $errs);
        self::assertEquals('[2, 20] LexError: Unclosed comment', (string) $errs[0]);
    }

    public function testUnknownCharLex(): void
    {
        $lexer = new Lexer(<<<GO
        var x int
        $
        var y int @
        GO);

        $lexer->lex();
        $errs = $lexer->getErrors();

        self::assertCount(2, $errs);
        self::assertEquals('[2, 11] LexError: Unknown character "$"', (string) $errs[0]);
        self::assertEquals('[3, 23] LexError: Unknown character "@"', (string) $errs[1]);
    }

    public function testUnterminatedStringLex(): void
    {
        $lexer = new Lexer(<<<GO
        var x int
        var y string = "str
        GO);

        $lexer->lex();
        $errs = $lexer->getErrors();

        self::assertCount(1, $errs);
        self::assertEquals('[2, 29] LexError: Unterminated string', (string) $errs[0]);
    }
}
