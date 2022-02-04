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
        [2, 7] Package
        [2, 12] Ident "main"
        [3, 0] Semicolon ;
        [4, 6] Import
        [4, 12] String ""fmt""
        [5, 0] Semicolon ;
        [6, 4] Func
        [6, 12] Ident "plusDiv"
        [6, 13] LeftParen (
        [6, 14] Ident "a"
        [6, 15] Comma ,
        [6, 17] Ident "b"
        [6, 18] Comma ,
        [6, 20] Ident "c"
        [6, 24] Ident "int"
        [6, 25] RightParen )
        [6, 29] Ident "int"
        [6, 31] LeftBrace {
        [7, 10] Return
        [7, 12] LeftParen (
        [7, 13] Ident "a"
        [7, 15] Plus +
        [7, 17] Ident "b"
        [7, 18] RightParen )
        [7, 20] Div /
        [7, 22] Ident "c"
        [8, 0] Semicolon ;
        [8, 1] RightBrace }
        [9, 0] Semicolon ;
        [10, 4] Func
        [10, 6] Ident "f"
        [10, 7] LeftParen (
        [10, 11] Ident "from"
        [10, 18] Ident "string"
        [10, 19] RightParen )
        [10, 21] LeftBrace {
        [11, 7] For
        [11, 9] Ident "i"
        [11, 12] ColonEq :=
        [11, 14] Int "0"
        [11, 15] Semicolon ;
        [11, 17] Ident "i"
        [11, 20] Less <
        [11, 21] Int "3"
        [11, 22] Semicolon ;
        [11, 24] Ident "i"
        [11, 26] Inc ++
        [11, 28] LeftBrace {
        [12, 11] Ident "fmt"
        [12, 12] Dot .
        [12, 19] Ident "Println"
        [12, 20] LeftParen (
        [12, 24] Ident "from"
        [12, 25] Comma ,
        [12, 29] String "":""
        [12, 30] Comma ,
        [12, 32] Ident "i"
        [12, 33] RightParen )
        [13, 0] Semicolon ;
        [13, 5] RightBrace }
        [14, 0] Semicolon ;
        [14, 1] RightBrace }
        [15, 0] Semicolon ;
        [16, 4] Func
        [16, 9] Ident "main"
        [16, 10] LeftParen (
        [16, 11] RightParen )
        [16, 13] LeftBrace {
        [17, 7] Ident "res"
        [17, 10] ColonEq :=
        [17, 18] Ident "plusDiv"
        [17, 19] LeftParen (
        [17, 20] Int "1"
        [17, 21] Comma ,
        [17, 23] Int "9"
        [17, 24] Comma ,
        [17, 26] Int "5"
        [17, 27] RightParen )
        [18, 0] Semicolon ;
        [18, 7] Ident "fmt"
        [18, 8] Dot .
        [18, 15] Ident "Println"
        [18, 16] LeftParen (
        [18, 26] String ""(1+9)/5=""
        [18, 27] Comma ,
        [18, 31] Ident "res"
        [18, 32] RightParen )
        [19, 0] Semicolon ;
        [20, 6] Go
        [20, 8] Ident "f"
        [20, 9] LeftParen (
        [20, 20] String ""goroutine""
        [20, 21] RightParen )
        [21, 0] Semicolon ;
        [21, 1] RightBrace }
        [21, 1] Semicolon ;
        [21, 1] Eof
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
        self::assertEquals('[2, 10] LexError: Unclosed comment', (string) $errs[0]);
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
        self::assertEquals('[2, 1] LexError: Unknown character "$"', (string) $errs[0]);
        self::assertEquals('[3, 11] LexError: Unknown character "@"', (string) $errs[1]);
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
        self::assertEquals('[2, 19] LexError: Unterminated string', (string) $errs[0]);
    }
}
