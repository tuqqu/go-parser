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
    public function testDataFiles(string $src, string $serialized): void
    {
        $parser = new Parser($src);
        $file = $parser->parse();

        self::assertEmpty($parser->getErrors());
        self::assertEquals(\unserialize($serialized), $file);
    }

    private static function syntaxFiles(): iterable
    {
        $path  = __DIR__ . '/data/';
        $files = [
            'src/syntax/generic_function.go' => 'serialized/syntax/generic_function.txt',
            'src/syntax/generic_typedef.go' => 'serialized/syntax/generic_typedef.txt',
            'src/syntax/interface.go' => 'serialized/syntax/interface.txt',
            'src/syntax/params.go' => 'serialized/syntax/params.txt',
            'src/syntax/declarations.go' => 'serialized/syntax/declarations.txt',
        ];

        foreach ($files as $srcFile => $serialized) {
            yield [
                \file_get_contents($path . $srcFile),
                \file_get_contents($path . $serialized),
            ];
        }
    }

    private static function exampleFiles(): iterable
    {
        $path  = __DIR__ . '/data/';
        $files = [
            'src/example/file1.go' => 'serialized/example/file1.txt',
            'src/example/file2.go' => 'serialized/example/file2.txt',
            'src/example/file3.go' => 'serialized/example/file3.txt',
            'src/example/file4.go' => 'serialized/example/file4.txt',
            'src/example/file5.go' => 'serialized/example/file5.txt',
            'src/example/file6.go' => 'serialized/example/file6.txt',
        ];

        foreach ($files as $srcFile => $serialized) {
            yield [
                \file_get_contents($path . $srcFile),
                \file_get_contents($path . $serialized),
            ];
        }
    }

    private static function assertIdent(string $expected, mixed $actual): void
    {
        self::assertInstanceOf(Ident::class, $actual);
        self::assertEquals($expected, $actual->name);
    }
}
