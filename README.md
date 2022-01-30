# GoParser
Dependency-free Golang parser written in PHP 8.1

## Installation
To install this package, run:

```
composer require tuqqu/go-parser
```

## Example
```php
$parser = new \GoParser\Parser(<<<GO
package main

import "fmt"

func main() {
    res := plus(1, 2)
    fmt.Println("1+2 =", res)
}
GO);

$ast = $parser->parse();
$errs = $parser->getErrors();
```

Parser can recover itself if it encounters errors, in this case it will continue parsing at the closest node it can recognise and also continue collecting errors. 
You can also ask it with `hasErrors()` whether there were errors during parsing or not. 

### Parsing single declarations
Sometimes you want to parse only a single declaration (e.g. a single function), instead of a full program:
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
Some Nodes are also have a bit different name due to brevity and consistency with others (e.g. `ExpressionList` vs `ExprList`), but for the most part the names are either the same or easily recognisable.

## CLI
Package comes with a CLI command, so you may try things out like this:

```
./vendor/bin/go-parser main.go
```

Use `--help` to see full usage description.

[1]: https://go.dev/ref/spec