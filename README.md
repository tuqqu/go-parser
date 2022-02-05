# GoParser
Golang parser written in PHP 8.1

## Installation
To install this package, run:

```
composer require tuqqu/go-parser
```

## Example
```php
$parser = new \GoParser\Parser('
package main

import "fmt"

func main() {
    res := plus(1, 2)
    fmt.Println("1+2 =", res)
}
');

$ast = $parser->parse();
$errs = $parser->getErrors();
```

Parser is able to recover itself if a parse error occurs, in this case it will continue parsing at the closest node it is able to recognise.
The resulting AST will be as full as possible, and you have to check `getErrors()` to see errors.

### Parsing single declarations
If you want, you may parse only a single declaration (e.g. a single function), instead of a fully defined Go program:
```php
$parser = new \GoParser\Parser(
    'func main() { var x int }', 
    mode: \GoParser\ParseMode::SingleDecl
);
$ast = $parser->parse();
```

## Abstract Syntax Tree

Parsing results in an Abstract Syntax Tree result.

Mostly the AST nodes structure follows closely the official Golang [specification][1], but for the sake of simplicity there are few exceptions.
Some Nodes may also have a bit different name for the sake of brevity and consistency with others (e.g. `ExpressionList` vs `ExprList`), but for the most part the names are either the same or easily recognisable.

## CLI
Package comes with a CLI command:

```
./vendor/bin/go-parser main.go [--flags]
```

By default, it uses a simple `NodeDumper` to print AST.

Use `--help` to see other options.

[1]: https://go.dev/ref/spec