<?php

declare(strict_types=1);

namespace GoParser;

use GoParser\Ast\AstNode;
use GoParser\Ast\Expr\Literal;
use GoParser\Ast\Expr\PrimaryExpr;
use GoParser\Ast\Expr\Type;
use GoParser\Ast\File;
use GoParser\Ast\Stmt\Decl;
use GoParser\Ast\Stmt\SimpleStmt;
use GoParser\Ast\Stmt\Stmt;
use GoParser\Lexer\Position;
use PhpParser\Node\Expr;

final class NodeDumper
{
    public function __construct(
        private readonly int $startingIndent = 0,
        private readonly mixed $stream = \STDOUT,
        private readonly string $indentWith = '__',
        private readonly bool $showFilename = false,
        private readonly bool $showPosition = true,
    ) {}

    public function dump(AstNode $node): void
    {
        switch (true) {
            case $node instanceof File:
                $this->printlnIndent(\sprintf('[File] %s', (string) $node->filename), 0);
                $this->printNode($node->package, 1);

                $this->printlnIndent('[Imports]: ', 1);
                foreach ($node->imports as $import) {
                    $this->printNode($import, 2);
                }

                $this->printlnIndent('[Decls]: ', 1);
                foreach ($node->decls as $decl) {
                    $this->printNode($decl, 2);
                }
                break;
            case $node instanceof Decl:
                $this->printlnIndent(\sprintf('[Decl] %s', self::name($node)), 0);
                $this->printNode($node, 0);
                break;
            default:
                $this->printNode($node, 0);
        }
    }

    private function printNode(AstNode $node, int $indent): void
    {
        $type = self::type($node);
        $pos = $this->getPos($node);

        $this->printlnIndent(\sprintf('%s %s', $type, $pos), $indent);

        foreach (self::props($node) as $prop) {
            $this->printProp($node, $prop, $indent);
        }
    }

    private static function name(AstNode $node): string
    {
        return (new \ReflectionClass($node))->getShortName();
    }

    private static function type(AstNode $node): string
    {
        $type = match (true) {
            $node instanceof Type => 'Type',
            $node instanceof PrimaryExpr => 'PrimaryExpr',
            $node instanceof Literal => 'Literal',
            $node instanceof Expr => 'Expr',
            $node instanceof SimpleStmt => 'SimpleStmt',
            $node instanceof Decl => 'Decl',
            $node instanceof Stmt => 'Stmt',
            default => null,
        };

        return $type === null
            ? self::name($node)
            : \sprintf("[%s] %s", $type, self::name($node));
    }

    private function printProp(AstNode $node, \ReflectionProperty $property, int $indent): void
    {
        $value = $property->getValue($node);

        if ($value instanceof Position) {
            return;
        }

        if ($value instanceof \UnitEnum) {
            $this->printlnIndent(\sprintf("%s: %s", $property->name, $value->name), $indent);
            return;
        }

        if ($value instanceof AstNode) {
            $this->printlnIndent(\sprintf("%s: ", $property->name), $indent);
            $this->printNode($value, $indent + 1);
            return;
        }

        if (\is_array($value)) {
            $this->printArray($value, $property, $indent);
            return;
        }

        $this->printlnIndent(\sprintf("%s: %s", $property->name, $value ?? 'null'), $indent);
    }

    private function printArray(array $value, \ReflectionProperty $property, int $indent): void
    {
        $this->printlnIndent(\sprintf("%s: ", $property->name), $indent);
        foreach ($value as $item) {
            if (\is_array($item)) {
                $this->printArray($item, $property, $indent);
            } else {
                $this->printNode($item, $indent);
            }
        }
    }

    /**
     * @return iterable<\ReflectionProperty>
     */
    private static function props(AstNode $node): iterable
    {
        $ref = new \ReflectionClass($node);
        foreach ($ref->getProperties() as $property) {
            yield $property;
        }
    }

    private function getPos(AstNode $node): string
    {
        if (!$this->showPosition) {
            return '';
        }

        foreach (self::props($node) as $prop) {
            $value = $prop->getValue($node);

            if ($value instanceof Position) {
                $pos = new Position(
                    $value->offset,
                    $value->line,
                    $this->showFilename
                        ? $value->filename
                        : null
                );

                return (string) $pos;
            }
        }

        return '';
    }

    private function indentStr(string $str, int $indent): string
    {
        return \str_repeat($this->indentWith, $indent) . $str;
    }

    private function printlnIndent(string $txt, int $indent): void
    {
        $this->println($this->indentStr($txt, $indent + $this->startingIndent));
    }

    private function println(string $txt): void
    {
        $this->print($txt . "\n");
    }

    private function print(string $txt): void
    {
        \fwrite($this->stream, $txt);
    }
}
