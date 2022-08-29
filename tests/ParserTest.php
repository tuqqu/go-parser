<?php

declare(strict_types=1);

namespace Tests\GoParser\Lexer;

use GoParser\Ast\Expr\Ident;
use GoParser\Ast\ImportSpec;
use GoParser\Ast\SpecType;
use GoParser\Ast\Stmt\ConstDecl;
use GoParser\Ast\Stmt\FuncDecl;
use GoParser\Ast\Stmt\TypeDecl;
use GoParser\Ast\Stmt\VarDecl;
use GoParser\Parser;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase
{
    public function testFile(): void
    {
        $parser = new Parser('
        package main
        
        import "fmt"
        
        func main() {}
        const FOO = "bar"
        var i int = 5
        type integer int
        ');
        $file = $parser->parse();

        self::assertFalse($parser->hasErrors());
        self::assertIdent("main", $file->package->identifier);

        self::assertCount(1, $file->imports);
        self::assertFalse($file->imports[0]->spec->isGroup());
        self::assertEquals(SpecType::Import, $file->imports[0]->spec->type());
        self::assertInstanceOf(ImportSpec::class, $file->imports[0]->spec);
        self::assertEquals('"fmt"', $file->imports[0]->spec->path->str);

        self::assertCount(4, $file->decls);
        self::assertInstanceOf(FuncDecl::class, $file->decls[0]);
        self::assertInstanceOf(ConstDecl::class, $file->decls[1]);
        self::assertInstanceOf(VarDecl::class, $file->decls[2]);
        self::assertInstanceOf(TypeDecl::class, $file->decls[3]);
    }

    /**
     * @dataProvider syntaxFiles
     * @dataProvider exampleFiles
     */
    public function testDataFiles(string $src, string $expectedAst): void
    {
        $parser = new Parser($src);
        $file = $parser->parse();
        $astJson = json_encode($file, \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR);

        self::assertEmpty($parser->getErrors());
        self::assertEquals($astJson, $expectedAst);
    }

    private static function syntaxFiles(): iterable
    {
        $path  = __DIR__ . '/data/';
        $files = [
            'src/syntax/generic_function.go' => 'ast/syntax/generic_function.json',
            'src/syntax/generic_typedef.go' => 'ast/syntax/generic_typedef.json',
            'src/syntax/interface.go' => 'ast/syntax/interface.json',
            'src/syntax/params.go' => 'ast/syntax/params.json',
            'src/syntax/declarations.go' => 'ast/syntax/declarations.json',
        ];

        foreach ($files as $srcFile => $ast) {
            yield [
                \file_get_contents($path . $srcFile),
                \file_get_contents($path . $ast),
            ];
        }
    }

    private static function exampleFiles(): iterable
    {
        $path  = __DIR__ . '/data/';
        $files = [
            'src/example/file1.go' => 'ast/example/file1.json',
            'src/example/file2.go' => 'ast/example/file2.json',
            'src/example/file3.go' => 'ast/example/file3.json',
            'src/example/file4.go' => 'ast/example/file4.json',
            'src/example/file5.go' => 'ast/example/file5.json',
            'src/example/file6.go' => 'ast/example/file6.json',
        ];

        foreach ($files as $srcFile => $ast) {
            yield [
                \file_get_contents($path . $srcFile),
                \file_get_contents($path . $ast),
            ];
        }
    }

    private static function assertIdent(string $expected, mixed $actual): void
    {
        self::assertInstanceOf(Ident::class, $actual);
        self::assertEquals($expected, $actual->name);
    }
}
