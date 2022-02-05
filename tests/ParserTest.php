<?php

declare(strict_types=1);

namespace Tests\GoParser\Lexer;

use GoParser\Parser;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase
{
    /**
     * @dataProvider dataFiles
     */
    public function testDataFiles(string $src): void
    {
        $parser = new Parser($src);
        $parser->parse();

        self::assertEmpty($parser->getErrors());
    }

    private static function dataFiles(): iterable
    {
        $files = [
            __DIR__ . '/data/file1.go',
            __DIR__ . '/data/file2.go',
            __DIR__ . '/data/file3.go',
        ];

        foreach ($files as $file) {
            yield [\file_get_contents($file)];
        }
    }
}
