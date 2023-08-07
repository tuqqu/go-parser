# GoParser
Golang parser written in PHP 8.1

## Installation
To install this package, run:

```
composer require tuqqu/go-parser
```

## Example
```php
use GoParser\Parser;

$program = <<<GO
    package main
    
    import "fmt"
    
    func main() {
        res := add(1, 2)
        fmt.Println("1+2 =", res)
    }
GO;

$parser = new Parser($program);
$ast = $parser->parse();
$errs = $parser->getErrors();
```

The parser is capable of recovering itself if a parse error occurs. In such cases, it will continue parsing at the closest node it can recognise.

The resulting Abstract Syntax Tree (AST) will be as complete as possible. You need to check `getErrors()` to identify any errors.

## Single declaration parsing

The parser can also handle a single declaration (e.g., a single function) instead of an entire Go program:
```php
use GoParser\{Parser, ParseMode};

$func = <<<GO
    func add(x, y int) int { 
        return x + y
    }
GO;

$parser = new Parser($func, mode: ParseMode::SingleDecl);
$decl = $parser->parseSingleDecl();
```

## Abstract Syntax Tree

Parsing results in an Abstract Syntax Tree. Refer to `src/Ast` for details.

For the most part, the structure of AST nodes closely follows the official Golang [specification][1].

Some nodes may have slightly different names (e.g., `ExpressionList` instead of `ExprList`), but in most cases, the names are the same or easily recognisable.

## CLI
Package comes with a CLI command:

```
./vendor/bin/go-parser main.go [--flags]
```

By default, it uses a simple `NodeDumper` to print AST.

Use `--help` to see other options.

[1]: https://go.dev/ref/spec