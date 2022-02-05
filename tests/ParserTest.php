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
     * @dataProvider dataFiles
     */
    public function testDataFiles(string $src, string $serialized): void
    {
        $parser = new Parser($src);
        $file = $parser->parse();

        self::assertEmpty($parser->getErrors());
        self::assertSame($serialized, \serialize($file));
    }

    private static function dataFiles(): iterable
    {
        $path  = __DIR__ . '/data/';
        $files = [
            'src/file1.go' => 'serialized/file1.txt',
            'src/file2.go' => 'serialized/file2.txt',
            'src/file3.go' => 'serialized/file3.txt',
            'src/file4.go' => 'serialized/file4.txt',
            'src/file5.go' => 'serialized/file5.txt',
            'src/file6.go' => 'serialized/file6.txt',
        ];

        foreach ($files as $srcFile => $serialized) {
            yield [
                \file_get_contents($path . $srcFile),
                \file_get_contents($path . $serialized)
            ];
        }
    }

    private static function assertIdent(string $expected, mixed $actual): void
    {
        self::assertInstanceOf(Ident::class, $actual);
        self::assertEquals($expected, $actual->name);
    }
}
