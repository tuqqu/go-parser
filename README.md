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

### Parsing single declarations
You may parse only a single declaration (e.g. a single function), instead of a fully defined Go program:
```php
$parser = new \GoParser\Parser(
    'func add(x, y int) { return x + y }', 
    mode: \GoParser\ParseMode::SingleDecl
);
$decl = $parser->parseSingleDecl();
```

Parser is able to recover itself if a parse error occurs, in this case it will continue parsing at the closest node it is able to recognise.

The resulting AST will be as full as possible, and you have to check `getErrors()` to see errors.


## Abstract Syntax Tree

Parsing results in an Abstract Syntax Tree result. See `src/Ast`.

For the most part the AST nodes structure follows closely the official Golang [specification][1].

Some Nodes may also have a bit different name (e.g. `ExpressionList` vs `ExprList`), but mostly the names are either the same or easily recognisable.

## CLI
Package comes with a CLI command:

```
./vendor/bin/go-parser main.go [--flags]
```

By default, it uses a simple `NodeDumper` to print AST.

Use `--help` to see other options.

[1]: https://go.dev/ref/spec