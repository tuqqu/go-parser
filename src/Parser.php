<?php

declare(strict_types=1);

namespace GoParser;

use GoParser\Ast\AstNode;
use GoParser\Ast\Expr\ChannelType;
use GoParser\Ast\Expr\InterfaceType;
use GoParser\Ast\Stmt\ConstDecl;
use GoParser\Ast\ConstSpec;
use GoParser\Ast\Exception\InvalidArgument;
use GoParser\Ast\Expr\ArrayType;
use GoParser\Ast\Expr\BinaryExpr;
use GoParser\Ast\Expr\CallExpr;
use GoParser\Ast\Expr\CompositeLit;
use GoParser\Ast\ElementList;
use GoParser\Ast\Expr\Expr;
use GoParser\Ast\Expr\FloatLit;
use GoParser\Ast\Expr\FullSliceExpr;
use GoParser\Ast\Expr\FuncLit;
use GoParser\Ast\Expr\FuncType;
use GoParser\Ast\Expr\GroupExpr;
use GoParser\Ast\Expr\Ident;
use GoParser\Ast\Expr\ImagLit;
use GoParser\Ast\Expr\IndexExpr;
use GoParser\Ast\Expr\IntLit;
use GoParser\Ast\KeyedElement;
use GoParser\Ast\Expr\MapType;
use GoParser\Ast\Expr\Operand;
use GoParser\Ast\ParamDecl;
use GoParser\Ast\Params;
use GoParser\Ast\Expr\ParenType;
use GoParser\Ast\Expr\PointerType;
use GoParser\Ast\Expr\PrimaryExpr;
use GoParser\Ast\Expr\RawStringLit;
use GoParser\Ast\Expr\RuneLit;
use GoParser\Ast\Expr\SelectorExpr;
use GoParser\Ast\Signature;
use GoParser\Ast\Expr\SimpleSliceExpr;
use GoParser\Ast\Expr\SliceExpr;
use GoParser\Ast\Expr\SliceType;
use GoParser\Ast\Expr\StringLit;
use GoParser\Ast\Expr\Type;
use GoParser\Ast\Expr\TypeAssertionExpr;
use GoParser\Ast\Expr\TypeName;
use GoParser\Ast\Expr\UnaryExpr;
use GoParser\Ast\ExprList;
use GoParser\Ast\File;
use GoParser\Ast\Stmt\FuncDecl;
use GoParser\Ast\GroupSpec;
use GoParser\Ast\IdentList;
use GoParser\Ast\Stmt\ImportDecl;
use GoParser\Ast\ImportSpec;
use GoParser\Ast\Keyword;
use GoParser\Ast\Stmt\MethodDecl;
use GoParser\Ast\Operator;
use GoParser\Ast\PackageClause;
use GoParser\Ast\Punctuation;
use GoParser\Ast\Stmt\ShortVarDecl;
use GoParser\Ast\Spec;
use GoParser\Ast\SpecType;
use GoParser\Ast\Stmt\AssignmentStmt;
use GoParser\Ast\Stmt\BlockStmt;
use GoParser\Ast\Stmt\BreakStmt;
use GoParser\Ast\CaseClause;
use GoParser\Ast\CaseLabel;
use GoParser\Ast\CommCase;
use GoParser\Ast\CommClause;
use GoParser\Ast\Stmt\ContinueStmt;
use GoParser\Ast\Stmt\Decl;
use GoParser\Ast\DefaultCase;
use GoParser\Ast\Stmt\DeferStmt;
use GoParser\Ast\Stmt\EmptyStmt;
use GoParser\Ast\ExprCaseClause;
use GoParser\Ast\Stmt\ExprStmt;
use GoParser\Ast\ExprSwitchCase;
use GoParser\Ast\Stmt\ExprSwitchStmt;
use GoParser\Ast\Stmt\FallthroughStmt;
use GoParser\Ast\ForClause;
use GoParser\Ast\Stmt\ForStmt;
use GoParser\Ast\Stmt\GoStmt;
use GoParser\Ast\Stmt\GotoStmt;
use GoParser\Ast\Stmt\IfStmt;
use GoParser\Ast\RangeClause;
use GoParser\Ast\Stmt\RecvStmt;
use GoParser\Ast\Stmt\ReturnStmt;
use GoParser\Ast\Stmt\SelectStmt;
use GoParser\Ast\Stmt\SendStmt;
use GoParser\Ast\Stmt\SimpleStmt;
use GoParser\Ast\Stmt\Stmt;
use GoParser\Ast\StmtList;
use GoParser\Ast\Stmt\SwitchStmt;
use GoParser\Ast\TypeCaseClause;
use GoParser\Ast\TypeList;
use GoParser\Ast\TypeSwitchCase;
use GoParser\Ast\TypeSwitchGuard;
use GoParser\Ast\Stmt\TypeSwitchStmt;
use GoParser\Ast\Stmt\VarDecl;
use GoParser\Ast\VarSpec;
use GoParser\Lexer\Lexeme;
use GoParser\Lexer\Lexer;
use GoParser\Lexer\Token;

final class Parser
{
    /** @var Lexeme[] */
    private readonly array $lexemes;
    private readonly ?AstNode $ast;
    private array $errors = [];
    private int $cur = 0;
    private bool $cfHeader = false;

    public function __construct(
        private readonly string $src,
        private readonly ?string $filename = null,
        private readonly ParseMode $mode = ParseMode::File,
        private readonly ?ErrorHandler $onError = null,
    ) {}

    public function parse(): ?AstNode
    {
        if (isset($this->ast)) {
            return $this->ast;
        }

        $lexer = new Lexer($this->src, $this->filename);
        $lexer->lex();
        $this->lexemes = $lexer->getLexemes();

        if ($lexer->hasErrors()) {
            $this->errors = $lexer->getErrors();
            $this->handleErrors();
            return $this->ast = null;
        }

        return match ($this->mode) {
            ParseMode::File => $this->parseFile(),
            ParseMode::SingleDecl => $this->parseSingleDecl(),
        };
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    private function parseFile(): ?File
    {
        $package = $this->parsePackageClause();
        $imports = $this->parseImports();

        $decls = $this->parseDecls();

        if ($this->hasErrors()) {
            $this->handleErrors();
        }

        return $this->ast = new File($package, $imports, $decls);
    }

    private function parseSingleDecl(): ?Decl
    {
        $decl = $this->parseDecl();

        if ($this->hasErrors()) {
            $this->handleErrors();
        }

        return $this->ast = $decl;
    }

    /**
     * @param callable(): Stmt $parser
     */
    private function tryParseWithRecover(callable $parser, bool $declMode = true): ?Stmt
    {
        try {
            return $parser();
        } catch (InvalidArgument $e) {
            $this->error($e->getMessage());
        } catch (ParseError) {
            $this->recover($declMode);
        }

        return null;
    }

    private function handleErrors(): void
    {
        foreach ($this->errors as $err) {
            $this->onError?->onError($err);
        }
    }

    private function error(string $msg): never
    {
        $err = new ParseError($msg, $this->peek()->pos);
        $this->errors[] = $err;

        throw $err;
    }

    private function parsePackageClause(): PackageClause
    {
        $package = $this->parseKeyword(Token::Package);
        $ident = $this->parseIdent();
        $this->consume(Token::Semicolon);

        return new PackageClause($package, $ident);
    }

    /**
     * @return ImportDecl[]
     */
    private function parseImports(): array
    {
        $imports = [];
        while ($this->match(Token::Import)) {
            $import = $this->tryParseWithRecover($this->parseImportDecl(...));
            if ($import !== null) {
                $imports[] = $import;
            }
        }

        return $imports;
    }

    private function parseImportDecl(): ImportDecl
    {
        $import = $this->parseKeyword(Token::Import);
        $spec = $this->parseSpec(SpecType::Import);
        $this->consume(Token::Semicolon);

        return new ImportDecl($import, $spec);
    }

    /**
     * @return Decl[]
     */
    private function parseDecls(): array
    {
        $decls = [];
        while (!$this->match(Token::Eof)) {
            $decl = $this->tryParseWithRecover($this->parseDecl(...));
            if ($decl !== null) {
                $decls[] = $decl;
            }
        }

        return $decls;
    }

    private function parseDecl(): Decl
    {
        return match ($this->peek()->token) {
            Token::Var => $this->parseVarDecl(),
            Token::Const => $this->parseConstDecl(),
//                Token::Type => $this->parseVarDecl(),
            Token::Func => $this->parseFuncOrMethodDecl(),
            default => $this->error(\sprintf('Declaration expected, got "%s"', $this->peek()->token->name)),
        };
    }

    /**
     * Parses both function and method declarations.
     * When receiver is non-null, then it's a method.
     */
    private function parseFuncOrMethodDecl(): FuncDecl|MethodDecl
    {
        $keyword = $this->parseKeyword(Token::Func);
        $receiver = $this->match(Token::LeftParen) ?
            $this->parseParams(false) :
            null;
        $name = $this->parseIdent();
        $sign = $this->parseSignature();
        $body = $this->match(Token::LeftBrace) ?
            $this->parseBlockStmt() :
            null;
        $this->consume(Token::Semicolon);

        return $receiver === null ?
            new FuncDecl($keyword, $name, $sign, $body) :
            new MethodDecl($keyword, $receiver, $name, $sign, $body);
    }

    private function parseStmt(): Stmt
    {
        return match ($this->peek()->token) {
            Token::Var,
            Token::Const,
            Token::Type => $this->parseDecl(),

            Token::Ident,
            Token::Int,
            Token::Float,
            Token::Imag,
            Token::Rune,
            Token::String,
            Token::RawString,
            Token::Func,
            Token::LeftParen,
            Token::LeftBracket,
            Token::Struct,
            Token::Map,
            Token::Chan,
            Token::Interface,
            Token::Plus,
            Token::Minus,
            Token::BitXor,
            Token::Arrow,
            Token::LogicNot,
            Token::BitAnd,
            Token::Semicolon => $this->parseSimpleStmt(),

            Token::Go => $this->parseGoStmt(),
            Token::Defer => $this->parseDeferStmt(),
            Token::Return => $this->parseReturnStmt(),
            Token::Goto => $this->parseGotoStmt(),
            Token::Fallthrough => $this->parseFallthroughStmt(),
            Token::Continue => $this->parseContinueStmt(),
            Token::Break => $this->parseBreakStmt(),
            Token::LeftBrace => $this->parseBlockStmt(),
            Token::If => $this->parseIfStmt(),
            Token::For => $this->parseForStmt(),
            Token::Switch => $this->parseSwitchStmt(),
            Token::Select => $this->parseSelectStmt(),
            default => $this->error(\sprintf('Unrecognised statement "%s"', $this->peek()->token->name)),
        };
    }

    private function parseSelectStmt(): SelectStmt
    {
        $keyword = $this->parseKeyword(Token::Select);
        $lBrace = $this->parsePunctuation(Token::LeftBrace);
        $cases = $this->parseCaseClauses(CommClause::class);
        $rBrace = $this->parsePunctuation(Token::RightBrace);

        return new SelectStmt($keyword, $lBrace, $cases, $rBrace);
    }

    /**
     * @return callable(): CaseLabel
     */
    private function labelParserMap(string $fqcn): callable
    {
        return match ($fqcn) {
            CommClause::class => $this->parseCommCase(...),
            ExprCaseClause::class => $this->parseExprSwitchCase(...),
            TypeCaseClause::class => $this->parseTypeSwitchCase(...),
        };
    }

    /**
     * @param string $clauseFqcn
     * @return CaseClause[]
     */
    private function parseCaseClauses(string $clauseFqcn): array
    {
        $labelParser = $this->labelParserMap($clauseFqcn);
        $cases = [];

        while (!$this->match(Token::RightBrace)) {
            $case = match ($this->peek()->token) {
                Token::Case => $labelParser(),
                Token::Default => $this->parseDefaultCase(),
                // unreachable
                default => $this->error(\sprintf('Case expected, got "%s"', $this->peek()->token->name)),
            };
            $stmts = $this->parseStmtList();

            $cases[] = new $clauseFqcn($case, $stmts);
        }

        return $cases;
    }

    private function parseDefaultCase(): DefaultCase
    {
        return new DefaultCase(
            $this->parseKeyword(Token::Default),
            $this->parsePunctuation(Token::Colon),
        );
    }

    private function parseCommCase(): CommCase
    {
        return new CommCase(
            $this->parseKeyword(Token::Case),
            $this->parseRecvOrSendStmt(),
            $this->parsePunctuation(Token::Colon),
        );
    }

    private function parseExprSwitchCase(): ExprSwitchCase
    {
        return new ExprSwitchCase(
            $this->parseKeyword(Token::Case),
            $this->parseExprList(),
            $this->parsePunctuation(Token::Colon),
        );
    }

    private function parseTypeSwitchCase(): TypeSwitchCase
    {
        return new TypeSwitchCase(
            $this->parseKeyword(Token::Case),
            $this->parseTypeList(),
            $this->parsePunctuation(Token::Colon),
        );
    }

    private function parseSendStmt(): SendStmt
    {
        return new SendStmt(
            $this->parseExpr(),
            $this->parseOperator(Token::Arrow),
            $this->parseExpr(),
        );
    }

    private function parseRecvOrSendStmt(): RecvStmt|SendStmt
    {
        switch (true) {
            case $this->checkAheadTill(Token::Colon, Token::Eq):
                $list = $this->parseExprList();
                $this->consume(Token::Eq);
                $expr = $this->parseExpr();
                break;
            case $this->checkAheadTill(Token::Colon, Token::ColonEq):
                $list = $this->parseIdentList();
                $this->consume(Token::ColonEq);
                $expr = $this->parseExpr();
                break;
            default:
                $list = null;
                $expr = $this->parseExpr();
                if ($this->match(Token::Arrow)) {
                    // SendStmt
                    return new SendStmt(
                        $expr,
                        $this->parseOperator(Token::Arrow),
                        $this->parseExpr(),
                    );
                }
        }

        return new RecvStmt($list, $expr);
    }

    private function parseForStmt(): ForStmt
    {
        $keyword = $this->parseKeyword(Token::For);
        $this->cfHeader = true;

        switch (true) {
            // for {}
            case $this->match(Token::LeftBrace):
                $iteration = null;
                break;
            // for range {}
            case $this->checkAheadTill(Token::LeftBrace, Token::Range):
                switch (true) {
                    // for range
                    case $this->match(Token::Range):
                        $list = null;
                        break;
                    // for ident :=
                    case $this->checkAheadTill(Token::Range, Token::ColonEq):
                        $list = $this->parseIdentList();
                        $this->consume(Token::ColonEq);
                        break;
                    // for expr = range {}
                    default:
                        $list = $this->parseExprList();
                        $this->consume(Token::Eq);
                }

                $range = $this->parseKeyword(Token::Range);
                $expr = $this->parseExpr();
                $iteration = new RangeClause($list, $range, $expr);
                break;
            // for expr; [expr; expr;] {}
            case $this->checkAheadTill(Token::LeftBrace, Token::Semicolon):
                $init = $this->parseSimpleStmt();
                $cond = $this->parseSimpleStmt();
                $post = $this->match(Token::LeftBrace) ?
                    null :
                    $this->parseSimpleStmt(true);

                $iteration = new ForClause($init, $cond, $post);
                break;
            // for expr {}
            default:
                $iteration = $this->parseExpr();
        }

        $this->cfHeader = false;
        $body = $this->parseBlockStmt();

        return new ForStmt($keyword, $iteration, $body);
    }

    private function parseGotoStmt(): GotoStmt
    {
        $keyword = $this->parseKeyword(Token::Goto);
        $label = $this->parseIdent();
        $this->consume(Token::Semicolon);

        return new GotoStmt($keyword, $label);
    }

    private function parseBreakStmt(): BreakStmt
    {
        $keyword = $this->parseKeyword(Token::Break);
        $label = $this->tryParseIdent();
        $this->consume(Token::Semicolon);

        return new BreakStmt($keyword, $label);
    }

    private function parseContinueStmt(): ContinueStmt
    {
        $keyword = $this->parseKeyword(Token::Continue);
        $label = $this->tryParseIdent();
        $this->consume(Token::Semicolon);

        return new ContinueStmt($keyword, $label);
    }

    private function parseFallthroughStmt(): FallthroughStmt
    {
        $keyword = $this->parseKeyword(Token::Fallthrough);
        $this->consume(Token::Semicolon);

        return new FallthroughStmt($keyword);
    }

    private function parseSimpleStmt(bool $skipSemi = false): SimpleStmt
    {
        // empty stmt
        if ($this->match(Token::Semicolon)) {
            return $this->parseEmptyStmt($skipSemi);
        }

        $exprs = $this->parseExprList();
        $simpleStmt = match ($this->peek()->token) {
            Token::Eq,
            Token::PlusEq,
            Token::MinusEq,
            Token::MulEq,
            Token::DivEq,
            Token::ModEq,
            Token::BitAndEq,
            Token::BitOrEq,
            Token::BitXorEq,
            Token::LeftShiftEq,
            Token::RightShiftEq,
            Token::BitAndNotEq => $this->parseAssignmentStmt($exprs),
            Token::ColonEq => $this->parseShortVarDecl($exprs),
            default => $this->exprStmtFromExprList($exprs),
        };

        if (!$skipSemi) {
            $this->consume(Token::Semicolon);
        }

        return $simpleStmt;
    }

    private function parseShortVarDecl(ExprList $list): ShortVarDecl
    {
        return new ShortVarDecl(
            IdentList::fromExprList($list),
            $this->parseOperator(Token::ColonEq),
            $this->parseExprList(),
        );
    }

    private function parseAssignmentStmt(ExprList $list): AssignmentStmt
    {
        return new AssignmentStmt(
            $list,
            $this->parseAnyOperator(),
            $this->parseExprList(),
        );
    }

    private function exprStmtFromExprList(ExprList $list): ExprStmt
    {
        return new ExprStmt($list->exprs[0]);
    }

    private function parseGoStmt(): GoStmt
    {
        $keyword = $this->parseKeyword(Token::Go);
        $call = $this->doParseCallExpr();
        $this->consume(Token::Semicolon);

        return new GoStmt($keyword, $call);
    }

    private function doParseCallExpr(): CallExpr
    {
        $expr = $this->parseExpr();

        if (!$expr instanceof CallExpr) {
            $this->error(\sprintf('Call expression expected, got "%s"', $expr::class));
        }

        return $expr;
    }

    private function parseEmptyStmt(bool $skipSemi = false): EmptyStmt
    {
        return new EmptyStmt(
            $skipSemi ?
                $this->peek()->pos :
                $this->consume(Token::Semicolon)->pos
        );
    }

    private function parseSwitchStmt(): SwitchStmt
    {
        $keyword = $this->parseKeyword(Token::Switch);
        $this->cfHeader = true;

        $init = $this->checkAheadTill(Token::LeftBrace, Token::Semicolon) ?
            $this->parseSimpleStmt() :
            null;

        if ($this->match(Token::LeftBrace)) {
            // ExprSwitchStmt
            $cond = null;
            $isTypeSwitch = false;
        } elseif ($this->checkAheadTill(Token::LeftBrace, Token::ColonEq)) {
            // TypeSwitchStmt with short decl
            $isTypeSwitch = true;
            $ident = $this->parseIdent();
            $this->consume(Token::ColonEq);
            $expr = $this->parsePrimaryExpr();
        } elseif ($this->checkAheadTill(Token::LeftBrace, Token::Dot, Token::LeftParen)) {
            // TypeSwitchStmt
            $ident = null;
            $isTypeSwitch = true;
            $this->consume(Token::ColonEq);
            $expr = $this->parsePrimaryExpr();
        } else {
            // ExprSwitchStmt
            $isTypeSwitch = false;
            $cond = $this->parseExpr();
        }

        $lBrace = $this->parsePunctuation(Token::LeftBrace);
        $this->cfHeader = false;

        $cases = $this->parseCaseClauses(
            $isTypeSwitch ?
                TypeCaseClause::class :
                ExprCaseClause::class
        );
        $rBrace = $this->parsePunctuation(Token::RightBrace);

        return $isTypeSwitch ?
            new TypeSwitchStmt(
                $keyword,
                $init,
                new TypeSwitchGuard($ident, $expr),
                $lBrace,
                $cases,
                $rBrace,
            ) :
            new ExprSwitchStmt(
                $keyword,
                $init,
                $cond,
                $lBrace,
                $cases,
                $rBrace,
            );
    }

    private function parseIfStmt(bool $nested = false): IfStmt
    {
        $if = $this->parseKeyword(Token::If);
        $this->cfHeader = true;

        if ($nested) {
            $init = null;
            $cond = null;
        } else {
            $init = $this->checkAheadTill(Token::LeftBrace, Token::Semicolon) ?
                $this->parseSimpleStmt() :
                null;
            $cond = $this->parseExpr();
        }
        $this->cfHeader = false;
        $body = $this->parseBlockStmt();

        if ($this->match(Token::Else)) {
            $else = $this->parseKeyword(Token::Else);
            $elseBody = match ($this->peek()->token) {
                Token::If => $this->parseIfStmt(true),
                Token::LeftBrace => $this->parseBlockStmt(),
                default => $this->error('Malformed else branch'),
            };
        } else {
            $else = null;
            $elseBody = null;
        }

        return new IfStmt($if, $init, $cond, $body, $else, $elseBody);
    }

    private function parseBlockStmt(): BlockStmt
    {
        return new BlockStmt(
            $this->parsePunctuation(Token::LeftBrace),
            $this->parseStmtList(),
            $this->parsePunctuation(Token::RightBrace)
        );
    }

    private function parseStmtList(): StmtList
    {
        $stmts = [];
        while (!$this->match(Token::Case, Token::Default, Token::RightBrace)) {
            $stmt = $this->tryParseWithRecover($this->parseStmt(...), false);
            if ($stmt !== null) {
                $stmts[] = $stmt;
            }

            if ($this->match(Token::Eof)) {
                break;
            }
        }

        return new StmtList($stmts);
    }

    private function parseDeferStmt(): DeferStmt
    {
        $keyword = $this->parseKeyword(Token::Defer);
        $call = $this->doParseCallExpr();
        $this->consume(Token::Semicolon);

        return new DeferStmt($keyword, $call);
    }

    private function parseReturnStmt(): ReturnStmt
    {
        $keyword = $this->parseKeyword(Token::Return);
        $exprs = $this->peek()->token === Token::Semicolon ?
            null :
            $this->parseExprList();

        $this->consume(Token::Semicolon);

        return new ReturnStmt($keyword, $exprs);
    }

    private function parseVarDecl(): VarDecl
    {
        $var = $this->parseKeyword(Token::Var);
        $spec = $this->parseSpec(SpecType::Var);
        $this->consume(Token::Semicolon);

        return new VarDecl($var, $spec);
    }

    private function parseConstDecl(): ConstDecl
    {
        $const = $this->parseKeyword(Token::Const);
        $spec = $this->parseSpec(SpecType::Const);
        $this->consume(Token::Semicolon);

        return new ConstDecl($const, $spec);
    }

    private function parseSpec(SpecType $type): Spec
    {
        /** @var callable(): Spec $parser */
        $parser = match ($type) {
            SpecType::Import => $this->parseImportSpec(...),
            SpecType::Var => $this->parseVarSpec(...),
            SpecType::Const => $this->parseConstSpec(...),
        };

        if ($this->match(Token::LeftParen)) {
            $spec = $this->parseGroupSpec($parser);
        } else {
            $spec = $parser();
        }

        return $spec;
    }

    private function parseVarSpec(): VarSpec
    {
        $identList = $this->parseIdentList();
        $type = $this->parseType();

        if ($this->consumeIf(Token::Eq) !== null) {
            $initList = $this->parseExprList();
        } else {
            $initList = null;
        }

        return new VarSpec($identList, $type, $initList);
    }

    private function parseConstSpec(): ConstSpec
    {
        $identList = $this->parseIdentList();
        $type = $this->parseType();
        $this->consume(Token::Eq);
        $initList = $this->parseExprList();

        return new ConstSpec($identList, $type, $initList);
    }

    private function parseExprList(): ExprList
    {
        $exprs = [];
        do {
            $exprs[] = $this->parseExpr();
        } while ($this->consumeIf(Token::Comma) !== null);

        return new ExprList($exprs);
    }

    private function parseExpr(): Expr
    {
        return $this->parseBinaryExpr(OpPrecedence::Or);
    }

    private function parseBinaryExpr(OpPrecedence $prec): Expr
    {
        $expr = $this->parseUnaryExpr();

        while (true) {
            $opToken = $this->peek()->token;
            $nPrec = OpPrecedence::fromToken($opToken);

            if ($nPrec->value < $prec->value) {
                break;
            }

            $op = $this->parseOperator($opToken);
            $nPrec = OpPrecedence::tryFrom($nPrec->value + 1);

            if ($nPrec === null) {
                break;
            }

            $rExpr = $this->parseBinaryExpr($nPrec);
            $expr = new BinaryExpr($expr, $op, $rExpr);
        }

        return $expr;
    }

    private function parseUnaryExpr(): Expr
    {
        // Unary ops: Math, Bitwise, Not, Pointer, Ref, Receive
        if ($this->match(
            Token::Plus,
            Token::Minus,
            Token::LogicNot,
            Token::BitXor,
            Token::Mul,
            Token::BitAnd,
            Token::Arrow,
        )) {
            $op = $this->parseAnyOperator();
            $unary = $this->parseUnaryExpr();

            return new UnaryExpr($op, $unary);
        }

        return $this->parsePrimaryExpr();
    }

    private function parsePrimaryExpr(): PrimaryExpr
    {
        $expr = $this->parseOperand();

        while (true) {
            switch ($this->peek()->token) {
                case Token::LeftBracket:
                    return $this->parseIndexOrSliceExpr($expr);
                case Token::LeftParen:
                    return $this->parseCallExpr($expr);
                case Token::LeftBrace:
                    if (!$this->cfHeader && self::canBeType($expr)) {
                        return $this->parseCompositeLit($expr);
                    }
                    break 2;
                case Token::Dot:
                    $this->advance();
                    return match ($this->peek()->token) {
                        Token::LeftParen => $this->parseTypeAssertionExpr($expr),
                        Token::Ident => $this->parseSelectorExpr($expr),
                        default => $this->error(\sprintf('Unexpected token "%s"', $this->peek()->token->name)),
                    };
                default:
                    break 2;
            }
        }

        return $expr;
    }

    private function parseSelectorExpr(Expr $expr): SelectorExpr
    {
        return new SelectorExpr($expr, $this->parseIdent());
    }

    private function parseTypeAssertionExpr(Expr $expr): TypeAssertionExpr
    {
        return new TypeAssertionExpr($expr, $this->parseParenType());
    }

    private static function canBeType(Expr $expr): bool
    {
        return $expr instanceof Ident || $expr instanceof SelectorExpr;
    }

    private function parseCompositeLit(Expr $expr): CompositeLit
    {
        $lBrace = $this->parsePunctuation(Token::LeftBrace);
        $elemList = $this->match(Token::RightBrace) ?
            null :
            $this->parseElementList();
        $rBrace = $this->parsePunctuation(Token::RightBrace);

        return new CompositeLit($expr, $lBrace, $elemList, $rBrace);
    }

    private function parseElementList(): ElementList
    {
        $elems = [];
        while (!$this->match(Token::RightBrace)) {
            $elems[] = $this->parseKeyedElement();

            $comma = $this->consumeIf(Token::Comma);

            if ($this->peek()->token === Token::RightBrace) {
                break;
            }

            if (!$comma) {
                $this->error('Comma expected in element list');
            }
        }

        return new ElementList($elems);
    }

    private function parseKeyedElement(): KeyedElement
    {
        $expr = $this->parseExpr();

        if (!$this->match(Token::Colon)) {
            return new KeyedElement(null, null, $expr);
        }

        $colon = $this->parsePunctuation(Token::Colon);
        $element = $this->parseExpr();

        return new KeyedElement($expr, $colon, $element);
    }

    private function parseCallExpr(Expr $expr): CallExpr
    {
        $lParen = $this->parsePunctuation(Token::LeftParen);
        $exprs = [];
        $ellipsis = null;
        $firstArg = true;

        while ($this->peek()->token !== Token::RightParen && $ellipsis === null) {
            if ($firstArg && $expr instanceof Ident && $expr->name === 'make') {
                $exprs[] = $this->parseType();
                $this->consume(Token::Comma);
                $firstArg = false;
                continue;
            }

            $exprs[] = $this->parseExpr();

            if ($this->peek()->token === Token::Ellipsis) {
                $ellipsis = $this->parsePunctuation(Token::Ellipsis);
            }

            $comma = $this->consumeIf(Token::Comma);

            if ($this->peek()->token === Token::RightParen) {
                break;
            }

            if (!$comma) {
                $this->error('Comma expected in call expression');
            }
        }

        $rParen = $this->parsePunctuation(Token::RightParen);

        return new CallExpr(
            $expr,
            $lParen,
            new ExprList($exprs),
            $ellipsis,
            $rParen,
        );
    }

    private function parseIndexOrSliceExpr(Expr $expr): IndexExpr|SliceExpr
    {
        $lBrack = $this->parsePunctuation(Token::LeftBracket);
        $indices = [];

        if ($this->peek()->token !== Token::Colon) {
            $indices[] = $this->parseExpr();
        }

        $colons = [];
        $maxColons = 2;
        $i = 0;

        while (\count($colons) < $maxColons && $this->peek()->token === Token::Colon) {
            $colons[$i] = $this->parsePunctuation(Token::Colon);

            if (!$this->match(Token::Colon, Token::RightBracket)) {
                $indices[++$i] = $this->parseExpr();
            }
        }

        $rBrack = $this->parsePunctuation(Token::RightBracket);

        return match (\count($colons)) {
            0 => new IndexExpr(
                $expr,
                $lBrack,
                $indices[0],
                $rBrack
            ),
            1 => new SimpleSliceExpr(
                $expr,
                $lBrack,
                $indices[0] ?? null,
                $colons[0],
                $indices[1] ?? null,
                $rBrack
            ),
            2 => new FullSliceExpr(
                $expr,
                $lBrack,
                $indices[0] ?? null,
                $colons[0],
                $indices[1] ?? $this->error('Wrong number of indices'),
                $colons[1],
                $indices[2]  ?? $this->error('Wrong number of indices'),
                $rBrack
            ),
        };
    }

    private function parseOperand(): Operand
    {
        return match ($this->peek()->token) {
            Token::Int => $this->parseIntLit(),
            Token::Float => $this->parseFloatLit(),
            Token::Imag => $this->parseImagLit(),
            Token::Rune => $this->parseRuneLit(),
            Token::String => $this->parseStringLit(),
            Token::RawString => $this->parseRawStringLit(),
            Token::Ident => $this->parseIdent(),
            Token::LeftParen => $this->parseGroupExpr(),
            Token::Func => $this->parseFuncLit(),
            default => $this->error(\sprintf('Unknown token "%s" in operand expression', $this->peek()->token->name)),
        };
    }

    private function parseGroupExpr(): GroupExpr
    {
        return new GroupExpr(
            $this->parsePunctuation(Token::LeftParen),
            $this->parseExpr(),
            $this->parsePunctuation(Token::RightParen),
        );
    }

    private function parseFuncLit(): FuncLit
    {
        $type = $this->parseFuncType();
        $body = $this->parseBlockStmt();

        return new FuncLit($type, $body);
    }

    private function parseFuncType(): FuncType
    {
        return new FuncType(
            $this->parseKeyword(Token::Func),
            $this->parseSignature(),
        );
    }

    private function parseSignature(): Signature
    {
        return new Signature(
            $this->parseParams(true),
            $this->parseResult(),
        );
    }

    private function parseParams(bool $variadic): Params
    {
        $lParen = $this->parsePunctuation(Token::LeftParen);
        $params = [];

        if (!$this->match(Token::RightParen)) {
            while (true) {
                $identList = $this->parseIdentList();
                if ($variadic) {
                    $ellipsis = $this->match(Token::Ellipsis) ?
                        $this->parsePunctuation(Token::Ellipsis) :
                        null;
                } else {
                    $ellipsis = null;
                }

                $type = $this->parseType();

                $params[] = new ParamDecl($identList, $ellipsis, $type);

                if (!$this->match(Token::Comma)) {
                    break;
                }

                $this->consume(Token::Comma);
            }
        }

        $rParen = $this->parsePunctuation(Token::RightParen);

        return new Params($lParen, $params, $rParen);
    }

    private function parseResult(): Params|Type|null
    {
        return $this->match(Token::LeftParen) ?
            $this->parseParams(false) :
            $this->parseType();
    }

    /**
     * @ 
     * @psalm-suppress UndefinedMethod
     */
    private function parseType(): ?Type
    {
        return match ($this->peek()->token) {
            Token::Ident => $this->parseTypeName(),
            Token::LeftBracket => $this->parseArrayOrSliceType(),
            Token::Struct => $this->parseStructType(), //todo
            Token::Mul => $this->parsePointerType(),
            Token::Func => $this->parseFuncType(),
            Token::Interface => $this->parseInterfaceType(),
            Token::Map => $this->parseMapType(),
            Token::Arrow, Token::Chan => $this->parseChannelType(),
            Token::LeftParen => $this->parseParenType(),
            default => null,
        };
    }

    private function doParseType(): Type
    {
        return $this->parseType() ?? $this->error('Type expected');
    }

    private function parseTypeList(): TypeList
    {
        $types = [];
        do {
            $types[] = $this->doParseType();
        } while ($this->consumeIf(Token::Comma) !== null);

        return new TypeList($types);
    }

    private function parseParenType(): ParenType
    {
        return new ParenType(
            $this->parsePunctuation(Token::LeftParen),
            $this->parseType(),
            $this->parsePunctuation(Token::RightParen)
        );
    }

    private function parseMapType(): MapType
    {
        return new MapType(
            $this->parseKeyword(Token::Map),
            $this->parsePunctuation(Token::LeftBracket),
            $this->parseType(),
            $this->parsePunctuation(Token::RightBracket),
            $this->parseType(),
        );
    }

    private function parsePointerType(): PointerType
    {
        return new PointerType(
            $this->parsePunctuation(Token::Mul),
            $this->parseType()
        );
    }

    private function parseChannelType(): ChannelType
    {
        $chans = [];
        do {
            $chan = match ($this->peek()->token) {
                Token::Chan => $this->parseKeyword(Token::Chan),
                Token::Arrow => $this->parseOperator(Token::Arrow),
                default => null,
            };

            if ($chan === null) {
                break;
            }

            $chans[] = $chan;
        } while (true);

        return new ChannelType(
            $chans,
            $this->parseType(),
        );
    }

    private function parseInterfaceType(): InterfaceType
    {
        $keyword = $this->parseKeyword(Token::Interface);
        $lBrace = $this->parsePunctuation(Token::LeftBrace);
        $methods = [];
        while (!$this->match(Token::RightBrace)) {
            $ident = $this->parseIdent();
            if ($this->match(Token::Semicolon)) {
                $methods[] = $ident;
            } else {
                $sign = $this->parseSignature();
                $methods[] = [$ident, $sign];
            }

            $this->consume(Token::Semicolon);
        }

        $rBrace = $this->parsePunctuation(Token::RightBrace);

        return new InterfaceType(
            $keyword,
            $lBrace,
            $methods,
            $rBrace,
        );
    }

    private function parseIdentList(): IdentList
    {
        $idents = [];
        do {
            $idents[] = $this->parseIdent();
        } while ($this->consumeIf(Token::Comma) !== null);

        return new IdentList($idents);
    }

    /**
     * @param callable(): Spec $parseSpec
     */
    private function parseGroupSpec(callable $parseSpec): GroupSpec
    {
        $lParen = $this->parsePunctuation(Token::LeftParen);
        /** @var Spec[] $specs */
        $specs = [];
        do {
            $specs[] = $parseSpec();
            $this->consume(Token::Semicolon);
        } while (!$this->match(Token::RightParen));

        $rParen = $this->parsePunctuation(Token::RightParen);
        $type = $specs[0]->type();

        return new GroupSpec($lParen, $specs, $rParen, $type);
    }

    private function parseImportSpec(): ImportSpec
    {
        $name = match (true) {
            $this->match(Token::Ident) => $this->parseIdent(),
            $this->match(Token::Dot) => $this->parsePunctuation(Token::Dot),
            default => null,
        };

        $path = $this->parseStringLit();

        return new ImportSpec($name, $path);
    }

    private function parseArrayOrSliceType(): ArrayType|SliceType
    {
        $lParen = $this->parsePunctuation(Token::LeftBracket);
        $size = match ($this->peek()->token) {
            Token::Ellipsis => $this->parsePunctuation(Token::Ellipsis),
            Token::RightBracket => null,
            default => $this->parseExpr(),
        };
        $rParen = $this->parsePunctuation(Token::RightBracket);
        $elemType = $this->parseType();

        return $size === null ?
            new SliceType($lParen, $rParen, $elemType) :
            new ArrayType($lParen, $size, $rParen, $elemType);
    }

    private function parseAnyOperator(): Operator
    {
        return Operator::fromLexeme($this->advance());
    }

    private function parseOperator(Token $token): Operator
    {
        return Operator::fromLexeme($this->consume($token));
    }

    private function parseIdent(): Ident
    {
        return Ident::fromLexeme($this->consume(Token::Ident));
    }

    private function tryParseIdent(): ?Ident
    {
        return $this->match(Token::Ident) ?
            Ident::fromLexeme($this->consume(Token::Ident)) :
            null;
    }

    private function parseTypeName(): TypeName
    {
        return TypeName::fromLexeme($this->consume(Token::Ident));
    }

    private function parseStringLit(): StringLit
    {
        return StringLit::fromLexeme($this->consume(Token::String));
    }

    private function parseRuneLit(): RuneLit
    {
        return RuneLit::fromLexeme($this->consume(Token::Rune));
    }

    private function parseIntLit(): IntLit
    {
        return IntLit::fromLexeme($this->consume(Token::Int));
    }

    private function parseFloatLit(): FloatLit
    {
        return FloatLit::fromLexeme($this->consume(Token::Float));
    }

    private function parseRawStringLit(): RawStringLit
    {
        return RawStringLit::fromLexeme($this->consume(Token::RawString));
    }

    private function parseImagLit(): ImagLit
    {
        return ImagLit::fromLexeme($this->consume(Token::Imag));
    }

    private function parseKeyword(Token $token): Keyword
    {
        return Keyword::fromLexeme($this->consume($token));
    }

    private function parsePunctuation(Token $token): Punctuation
    {
        return Punctuation::fromLexeme($this->consume($token));
    }

    private function consume(Token $token): Lexeme
    {
        return $this->match($token) ?
            $this->advance() :
            $this->error(\sprintf(
                'Unexpected token "%s", expected "%s"',
                $this->peek()->token->name,
                $token->name,
            ));
    }

    private function consumeIf(Token ...$tokens): ?Lexeme
    {
        foreach ($tokens as $token) {
            if ($this->match($token)) {
                return $this->advance();
            }
        }

        return null;
    }

    private function advance(): Lexeme
    {
        if (!$this->isAtEnd()) {
            ++$this->cur;
        }

        return $this->prev();
    }

    private function peek(): Lexeme
    {
        return $this->peekBy(0);
    }

    private function match(Token ...$tokens): bool
    {
        return \in_array($this->peek()->token, $tokens, true);
    }

    private function isAtEnd(): bool
    {
        return $this->peek()->token === Token::Eof;
    }

    private function prev(): Lexeme
    {
        return $this->peekBy(-1);
    }

    private function checkAheadTill(Token $till, Token ...$needles): bool
    {
        $i = 0;
        $j = 0;
        $len = \count($needles);
        $needle = $needles[$j];

        while (($peeked = $this->peekBy($i++)->token) !== $till) {
            if ($peeked === $needle) {
                if (++$j === $len) {
                    return true;
                }

                $needle = $needles[$j];
            }
        }

        return false;
    }

    private function peekBy(int $by): Lexeme
    {
        do {
            $lexeme = $this->lexemes[$this->cur + $by] ?? null;

            if ($lexeme === null) {
                break;
            }

            if (!self::isToSkip($lexeme)) {
                return $lexeme;
            }

            ++$this->cur;
        } while (!$this->isAtEnd());

        throw new \OutOfBoundsException('Cannot peek that far');
    }

    private function recover(bool $declMode): void
    {
        while ($this->peek()->token !== Token::Eof) {
            if (!$declMode && $this->prev()->token === Token::Semicolon) {
                return;
            }

            if ($this->match(
                Token::Struct,
                Token::Func,
                Token::Var,
                Token::Const,
                Token::Type,
            )) {
                return;
            }

            if (!$declMode && $this->match(
                Token::If,
                Token::For,
                Token::Return,
                Token::Switch,
                Token::Select,
                Token::Go,
                Token::Defer,
            )) {
                return;
            }

            $this->advance();
        }
    }

    private static function isToSkip(Lexeme $lexeme): bool
    {
        return $lexeme->token === Token::Comment || $lexeme->token === Token::MultilineComment;
    }
}
