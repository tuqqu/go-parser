<?php

declare(strict_types=1);

namespace GoParser;

use GoParser\Ast\AliasDecl;
use GoParser\Ast\CaseClause;
use GoParser\Ast\CaseLabel;
use GoParser\Ast\CommCase;
use GoParser\Ast\CommClause;
use GoParser\Ast\ConstSpec;
use GoParser\Ast\DefaultCase;
use GoParser\Ast\ElementList;
use GoParser\Ast\Expr\ArrayType;
use GoParser\Ast\Expr\BinaryExpr;
use GoParser\Ast\Expr\CallExpr;
use GoParser\Ast\Expr\ChannelType;
use GoParser\Ast\Expr\CompositeLit;
use GoParser\Ast\Expr\Expr;
use GoParser\Ast\Expr\FloatLit;
use GoParser\Ast\Expr\FullSliceExpr;
use GoParser\Ast\Expr\FuncLit;
use GoParser\Ast\Expr\FuncType;
use GoParser\Ast\Expr\GroupExpr;
use GoParser\Ast\Expr\Ident;
use GoParser\Ast\Expr\ImagLit;
use GoParser\Ast\Expr\IndexExpr;
use GoParser\Ast\Expr\InterfaceType;
use GoParser\Ast\Expr\IntLit;
use GoParser\Ast\Expr\MapType;
use GoParser\Ast\Expr\Operand;
use GoParser\Ast\Expr\ParenType;
use GoParser\Ast\Expr\PointerType;
use GoParser\Ast\Expr\PrimaryExpr;
use GoParser\Ast\Expr\QualifiedTypeName;
use GoParser\Ast\Expr\RawStringLit;
use GoParser\Ast\Expr\RuneLit;
use GoParser\Ast\Expr\SelectorExpr;
use GoParser\Ast\Expr\SimpleSliceExpr;
use GoParser\Ast\Expr\SingleTypeName;
use GoParser\Ast\Expr\SliceExpr;
use GoParser\Ast\Expr\SliceType;
use GoParser\Ast\Expr\StringLit;
use GoParser\Ast\Expr\StructType;
use GoParser\Ast\Expr\Type;
use GoParser\Ast\Expr\TypeAssertionExpr;
use GoParser\Ast\Expr\TypeElem;
use GoParser\Ast\Expr\TypeName;
use GoParser\Ast\Expr\TypeTerm;
use GoParser\Ast\Expr\UnaryExpr;
use GoParser\Ast\Expr\UnderlyingType;
use GoParser\Ast\ExprCaseClause;
use GoParser\Ast\ExprList;
use GoParser\Ast\ExprSwitchCase;
use GoParser\Ast\FieldDecl;
use GoParser\Ast\File;
use GoParser\Ast\ForClause;
use GoParser\Ast\GroupSpec;
use GoParser\Ast\IdentList;
use GoParser\Ast\ImportSpec;
use GoParser\Ast\KeyedElement;
use GoParser\Ast\Keyword;
use GoParser\Ast\MethodElem;
use GoParser\Ast\Operator;
use GoParser\Ast\PackageClause;
use GoParser\Ast\ParamDecl;
use GoParser\Ast\Params;
use GoParser\Ast\Punctuation;
use GoParser\Ast\RangeClause;
use GoParser\Ast\Signature;
use GoParser\Ast\Spec;
use GoParser\Ast\SpecType;
use GoParser\Ast\Stmt\AssignmentStmt;
use GoParser\Ast\Stmt\BlockStmt;
use GoParser\Ast\Stmt\BreakStmt;
use GoParser\Ast\Stmt\ConstDecl;
use GoParser\Ast\Stmt\ContinueStmt;
use GoParser\Ast\Stmt\Decl;
use GoParser\Ast\Stmt\DeferStmt;
use GoParser\Ast\Stmt\EmptyStmt;
use GoParser\Ast\Stmt\ExprStmt;
use GoParser\Ast\Stmt\ExprSwitchStmt;
use GoParser\Ast\Stmt\FallthroughStmt;
use GoParser\Ast\Stmt\ForStmt;
use GoParser\Ast\Stmt\FuncDecl;
use GoParser\Ast\Stmt\GoStmt;
use GoParser\Ast\Stmt\GotoStmt;
use GoParser\Ast\Stmt\IfStmt;
use GoParser\Ast\Stmt\ImportDecl;
use GoParser\Ast\Stmt\IncDecStmt;
use GoParser\Ast\Stmt\LabeledStmt;
use GoParser\Ast\Stmt\MethodDecl;
use GoParser\Ast\Stmt\RecvStmt;
use GoParser\Ast\Stmt\ReturnStmt;
use GoParser\Ast\Stmt\SelectStmt;
use GoParser\Ast\Stmt\SendStmt;
use GoParser\Ast\Stmt\ShortVarDecl;
use GoParser\Ast\Stmt\SimpleStmt;
use GoParser\Ast\Stmt\Stmt;
use GoParser\Ast\Stmt\SwitchStmt;
use GoParser\Ast\Stmt\TypeDecl;
use GoParser\Ast\Stmt\TypeSwitchStmt;
use GoParser\Ast\Stmt\VarDecl;
use GoParser\Ast\StmtList;
use GoParser\Ast\TypeCaseClause;
use GoParser\Ast\TypeDef;
use GoParser\Ast\TypeList;
use GoParser\Ast\TypeParamDecl;
use GoParser\Ast\TypeParams;
use GoParser\Ast\TypeSpec;
use GoParser\Ast\TypeSwitchCase;
use GoParser\Ast\TypeSwitchGuard;
use GoParser\Ast\VarSpec;
use GoParser\Exception\InvalidArgument;
use GoParser\Exception\ParseModeError;
use GoParser\Lexer\Lexeme;
use GoParser\Lexer\Lexer;
use GoParser\Lexer\Token;

final class Parser
{
    /** @var Lexeme[] */
    private array $lexemes = [];
    /** @var Error[] */
    private array $errors = [];
    private ?File $ast = null;
    private ?Decl $decl = null;
    private int $cur = 0;
    private bool $cfHeader = false;
    private bool $finished = false;

    public function __construct(
        private readonly string $src,
        private readonly ?string $filename = null,
        private readonly ParseMode $mode = ParseMode::File,
        private readonly ?ErrorHandler $onError = null,
    ) {}

    /**
     * Parse a source file, that starts with a package clause.
     */
    public function parse(): ?File
    {
        $this->expectMode(ParseMode::File);

        if ($this->finished) {
            return $this->ast;
        }

        $lexer = new Lexer($this->src, $this->filename);
        $lexer->lex();
        $this->lexemes = $lexer->getLexemes();

        if ($lexer->hasErrors()) {
            $this->errors = $lexer->getErrors();
            $this->handleErrors();
            $this->finishParsing();

            return $this->ast = null;
        }

        return $this->parseFile();
    }

    /**
     * Parse a single declaration:
     * One of these: Function, variable, constant, import, type.
     */
    public function parseSingleDecl(): ?Decl
    {
        $this->expectMode(ParseMode::SingleDecl);

        if ($this->finished) {
            return $this->decl;
        }

        $lexer = new Lexer($this->src, $this->filename);
        $lexer->lex();
        $this->lexemes = $lexer->getLexemes();

        if ($lexer->hasErrors()) {
            $this->errors = $lexer->getErrors();
            $this->handleErrors();
            $this->finishParsing();

            return $this->decl = null;
        }

        $decl = $this->parseDecl();
        $this->finishParsing();

        if ($this->hasErrors()) {
            $this->handleErrors();
        }

        return $this->decl = $decl;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    private function parseFile(): File
    {
        $package = $this->parsePackageClause();
        $imports = $this->parseImports();
        $decls = $this->parseDecls();
        $this->finishParsing();

        if ($this->hasErrors()) {
            $this->handleErrors();
        }

        return $this->ast = new File($package, $imports, $decls, $this->filename);
    }

    private function finishParsing(): void
    {
        $this->finished = true;
    }

    private function expectMode(ParseMode $mode): void
    {
        if ($this->mode !== $mode) {
            throw new ParseModeError($mode, $this->mode);
        }
    }

    /**
     * @template T of Stmt
     * @param callable(): T $parser
     * @return T|null
     */
    private function tryParseWithRecover(callable $parser, ParseMode $mode): ?Stmt
    {
        try {
            return $parser();
        } catch (InvalidArgument $e) {
            $this->error($e->getMessage());
        } catch (ParseError) {
            $this->recover($mode);
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
        $this->parseSemicolon();

        return new PackageClause($package, $ident);
    }

    /**
     * @return ImportDecl[]
     */
    private function parseImports(): array
    {
        $imports = [];
        while ($this->match(Token::Import)) {
            $import = $this->tryParseWithRecover(
                $this->parseImportDecl(...),
                $this->mode,
            );
            if ($import !== null) {
                $imports[] = $import;
            }
        }

        return $imports;
    }

    private function parseImportDecl(): ImportDecl
    {
        $import = $this->parseKeyword(Token::Import);
        /** @var ImportSpec|GroupSpec $spec */
        $spec = $this->parseSpec(SpecType::Import);
        $this->parseSemicolon();

        return new ImportDecl($import, $spec);
    }

    /**
     * @return Decl[]
     */
    private function parseDecls(): array
    {
        $decls = [];
        while (!$this->isAtEnd()) {
            $decl = $this->tryParseWithRecover(
                $this->parseDecl(...),
                $this->mode,
            );
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
            Token::Type => $this->parseTypeDecl(),
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

        $typeParams = $receiver === null && $this->match(Token::LeftBracket) ?
            $this->parseTypeParams() :
            null;

        $sign = $this->parseSignature();

        $body = $this->match(Token::LeftBrace) ?
            $this->parseBlockStmt() :
            null;

        $this->parseSemicolon();

        return $receiver === null ?
            new FuncDecl($keyword, $name, $typeParams, $sign, $body) :
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
            Token::Plus,
            Token::Minus,
            Token::BitXor,
            Token::Arrow,
            Token::Mul,
            Token::LogicNot,
            Token::BitAnd,
            Token::Struct,
            Token::Map,
            Token::Chan,
            Token::Interface,
            Token::Func,
            Token::LeftParen,
            Token::LeftBracket,
            Token::Semicolon => $this->parseSimpleOrLabeledStmt(),

            Token::If => $this->parseIfStmt(),
            Token::For => $this->parseForStmt(),
            Token::Switch => $this->parseSwitchStmt(),
            Token::Select => $this->parseSelectStmt(),

            Token::LeftBrace => $this->parseBlockStmt(),

            Token::Go => $this->parseGoStmt(),
            Token::Defer => $this->parseDeferStmt(),

            Token::Return => $this->parseReturnStmt(),
            Token::Goto => $this->parseGotoStmt(),
            Token::Fallthrough => $this->parseFallthroughStmt(),
            Token::Continue => $this->parseContinueStmt(),
            Token::Break => $this->parseBreakStmt(),

            default => $this->error(\sprintf('Unrecognised statement "%s"', $this->peek()->token->name)),
        };
    }

    private function parseTypeParams(): TypeParams
    {
        $lParen = $this->parsePunctuation(Token::LeftBracket);
        $params = [];

        while (!$this->match(Token::RightBracket)) {
            $params[] = $this->parseTypeParamDecl();

            if (!$this->match(Token::Comma)) {
                break;
            }

            $this->consume(Token::Comma);
        }


        $rParen = $this->parsePunctuation(Token::RightBracket);

        return new TypeParams($lParen, $params, $rParen);
    }

    private function parseTypeParamDecl(): TypeParamDecl
    {
        $identList = $this->parseIdentList();
        $typeConstraint = $this->parseTypeElem();

        return new TypeParamDecl($identList, $typeConstraint);
    }

    private function parseTypeElem(): TypeElem
    {
        $terms = [];
        $terms[] = $this->parseTypeTerm();

        while ($this->consumeIf(Token::BitOr) !== null) {
            $terms[] = $this->parseTypeTerm();
        }

        return new TypeElem($terms);
    }

    private function parseTypeTerm(): TypeTerm
    {
        $tilda = $this->tryParseOperator(Token::Tilda);
        $type = $this->parseType();

        return $tilda !== null ?
            new UnderlyingType($tilda, $type) :
            $type;
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
     * @psalm-param class-string<CaseClause> $clauseFqcn
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
     * @template T of CaseClause
     * @psalm-param class-string<T> $clauseFqcn
     * @psalm-return T[]
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

    private function parseSendStmt(Expr $expr): SendStmt
    {
        return new SendStmt(
            $expr,
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
                    return $this->parseSendStmt($expr);
                }
        }

        return new RecvStmt($list, $expr);
    }

    private function parseForStmt(): ForStmt
    {
        $keyword = $this->parseKeyword(Token::For);
        $this->inCfHeader();

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
                        $op = null;
                        break;
                    // for ident :=
                    case $this->checkAheadTill(Token::Range, Token::ColonEq):
                        $list = $this->parseIdentList();
                        $op = $this->parseOperator(Token::ColonEq);
                        break;
                    // for expr = range {}
                    default:
                        $list = $this->parseExprList();
                        $op = $this->parseOperator(Token::Eq);
                }

                $range = $this->parseKeyword(Token::Range);
                $expr = $this->parseExpr();
                $iteration = new RangeClause($list, $op, $range, $expr);
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

        $this->outCfHeader();
        $body = $this->parseBlockStmt();

        return new ForStmt($keyword, $iteration, $body);
    }

    private function parseGotoStmt(): GotoStmt
    {
        $keyword = $this->parseKeyword(Token::Goto);
        $label = $this->parseIdent();
        $this->parseSemicolon();

        return new GotoStmt($keyword, $label);
    }

    private function parseBreakStmt(): BreakStmt
    {
        $keyword = $this->parseKeyword(Token::Break);
        $label = $this->tryParseIdent();
        $this->parseSemicolon();

        return new BreakStmt($keyword, $label);
    }

    private function parseContinueStmt(): ContinueStmt
    {
        $keyword = $this->parseKeyword(Token::Continue);
        $label = $this->tryParseIdent();
        $this->parseSemicolon();

        return new ContinueStmt($keyword, $label);
    }

    private function parseFallthroughStmt(): FallthroughStmt
    {
        $keyword = $this->parseKeyword(Token::Fallthrough);
        $this->parseSemicolon();

        return new FallthroughStmt($keyword);
    }

    private function parseSimpleOrLabeledStmt(bool $skipSemi = false): SimpleStmt|LabeledStmt
    {
        // empty stmt
        if ($this->match(Token::Semicolon)) {
            return $this->parseEmptyStmt($skipSemi);
        }

        $exprs = $this->parseExprList();

        $simpleOrLabeledStmt = match ($this->peek()->token) {
            Token::ColonEq => $this->parseShortVarDecl($exprs),
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
            Token::Inc,
            Token::Dec => $this->parseIncDecStmt($exprs),
            Token::Colon => $this->parseLabeledStmt($exprs),
            Token::Arrow => $this->parseSendStmt($this->exprFromExprList($exprs)),
            default => $this->exprStmtFromExprList($exprs),
        };

        if (!$skipSemi && $simpleOrLabeledStmt instanceof SimpleStmt) {
            $this->parseSemicolon();
        }

        return $simpleOrLabeledStmt;
    }

    private function parseSimpleStmt(bool $skipSemi = false): SimpleStmt
    {
        $stmt = $this->parseSimpleOrLabeledStmt($skipSemi);

        if (!$stmt instanceof SimpleStmt) {
            $this->error(\sprintf('Simple statement expected, got "%s"', $stmt::class));
        }

        return $stmt;
    }

    private function parseLabeledStmt(ExprList $list): LabeledStmt
    {
        $ident = $list->exprs[0] ?? null;

        if ($ident === null) {
            $this->error('Expected label');
        }

        if (!$ident instanceof Ident) {
            $this->error(
                \sprintf('Label expected to be an identifier, got %s', $ident::class)
            );
        }

        return new LabeledStmt(
            $ident,
            $this->parsePunctuation(Token::Colon),
            $this->parseStmt(),
        );
    }

    private function parseShortVarDecl(ExprList $list): ShortVarDecl
    {
        return new ShortVarDecl(
            IdentList::fromExprList($list),
            $this->parseOperator(Token::ColonEq),
            $this->parseExprList(),
        );
    }

    private function parseIncDecStmt(ExprList $list): IncDecStmt
    {
        return new IncDecStmt(
            $this->exprFromExprList($list),
            match ($this->peek()->token) {
                Token::Inc => $this->parseOperator(Token::Inc),
                Token::Dec => $this->parseOperator(Token::Dec),
                default => $this->error('Wrong operator in IncDecStmt'), // unreachable
            },
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

    private function exprFromExprList(ExprList $list): Expr
    {
        $expr = $list->exprs[0] ?? null;

        if ($expr === null) {
            $this->error('Expected single expression instead of an Expression list', );
        }

        return $expr;
    }

    private function exprStmtFromExprList(ExprList $list): ExprStmt
    {
        return new ExprStmt($this->exprFromExprList($list));
    }

    private function parseGoStmt(): GoStmt
    {
        $keyword = $this->parseKeyword(Token::Go);
        $call = $this->doParseCallExpr();
        $this->parseSemicolon();

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
        $this->inCfHeader();

        $init = $this->checkAheadTill(Token::LeftBrace, Token::Semicolon) ?
            $this->parseSimpleStmt() :
            null;

        // ExprSwitchStmt
        // switch {}
        if ($this->match(Token::LeftBrace)) {
            $this->outCfHeader();

            return $this->finishExprSwitchStmt($keyword, $init, null);
        }

        // TypeSwitchStmt with short decl
        // switch x := y.type {}
        if ($this->checkAheadTill(Token::LeftBrace, Token::ColonEq)) {
            $ident = $this->parseIdent();
            $this->consume(Token::ColonEq);
            $expr = $this->parsePrimaryExpr();
            $this->outCfHeader();

            return $this->finishTypeSwitchStmt(
                $keyword,
                $init,
                new TypeSwitchGuard($ident, $expr)
            );
        }

        // TypeSwitchStmt
        // switch type {}
        if ($this->checkAheadTill(Token::LeftBrace, Token::Dot, Token::LeftParen)) {
            $expr = $this->parsePrimaryExpr();
            $this->outCfHeader();

            return $this->finishTypeSwitchStmt(
                $keyword,
                $init,
                new TypeSwitchGuard(null, $expr)
            );
        }

        // ExprSwitchStmt
        // switch expr {}
        $cond = $this->parseExpr();
        $this->outCfHeader();

        return $this->finishExprSwitchStmt($keyword, $init, $cond);
    }

    private function finishTypeSwitchStmt(Keyword $keyword, ?SimpleStmt $init, TypeSwitchGuard $guard): TypeSwitchStmt
    {
        $lBrace = $this->parsePunctuation(Token::LeftBrace);
        $cases = $this->parseCaseClauses(TypeCaseClause::class);
        $rBrace = $this->parsePunctuation(Token::RightBrace);

        return new TypeSwitchStmt($keyword, $init, $guard, $lBrace, $cases, $rBrace);
    }

    private function finishExprSwitchStmt(Keyword $keyword, ?SimpleStmt $init, ?Expr $cond): ExprSwitchStmt
    {
        $lBrace = $this->parsePunctuation(Token::LeftBrace);
        $cases = $this->parseCaseClauses(ExprCaseClause::class);
        $rBrace = $this->parsePunctuation(Token::RightBrace);

        return new ExprSwitchStmt($keyword, $init, $cond, $lBrace, $cases, $rBrace);
    }

    private function parseIfStmt(): IfStmt
    {
        $if = $this->parseKeyword(Token::If);
        $this->inCfHeader();

        $init = $this->checkAheadTill(Token::LeftBrace, Token::Semicolon) ?
            $this->parseSimpleStmt() :
            null;

        $cond = $this->parseExpr();
        $this->outCfHeader();
        $body = $this->parseBlockStmt();

        if ($this->match(Token::Else)) {
            $else = $this->parseKeyword(Token::Else);
            $elseBody = match ($this->peek()->token) {
                Token::If => $this->parseIfStmt(),
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
            $stmt = $this->tryParseWithRecover(
                $this->parseStmt(...),
                ParseMode::SingleDecl
            );
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
        $this->parseSemicolon();

        return new DeferStmt($keyword, $call);
    }

    private function parseReturnStmt(): ReturnStmt
    {
        $keyword = $this->parseKeyword(Token::Return);
        $exprs = $this->match(Token::Semicolon) ?
            null :
            $this->parseExprList();

        $this->parseSemicolon();

        return new ReturnStmt($keyword, $exprs);
    }

    private function parseVarDecl(): VarDecl
    {
        $var = $this->parseKeyword(Token::Var);
        /** @var VarSpec|GroupSpec $spec */
        $spec = $this->parseSpec(SpecType::Var);
        $this->parseSemicolon();

        return new VarDecl($var, $spec);
    }

    private function parseConstDecl(): ConstDecl
    {
        $const = $this->parseKeyword(Token::Const);
        /** @var ConstSpec|GroupSpec $spec */
        $spec = $this->parseSpec(SpecType::Const);
        $this->parseSemicolon();

        return new ConstDecl($const, $spec);
    }

    private function parseTypeDecl(): TypeDecl
    {
        $type = $this->parseKeyword(Token::Type);
        /** @var TypeSpec|GroupSpec $spec */
        $spec = $this->parseSpec(SpecType::Type);
        $this->parseSemicolon();

        return new TypeDecl($type, $spec);
    }

    private function parseSpec(SpecType $type): Spec
    {
        if ($this->match(Token::LeftParen)) {
            $spec = $this->parseGroupSpec($type);
        } else {
            $spec = $this->matchSpecParser($type)(true);
        }

        return $spec;
    }

    /**
     * @return callable(bool): Spec
     */
    private function matchSpecParser(SpecType $type): callable
    {
        return match ($type) {
            SpecType::Import => $this->parseImportSpec(...),
            SpecType::Var => $this->parseVarSpec(...),
            SpecType::Const => $this->parseConstSpec(...),
            SpecType::Type => $this->parseTypeSpec(...),
        };
    }

    private function parseVarSpec(bool $_): VarSpec
    {
        $identList = $this->parseIdentList();
        $type = $this->tryParseType();

        if ($this->consumeIf(Token::Eq) !== null) {
            $initList = $this->parseExprList();
        } else {
            $initList = null;
        }

        return new VarSpec($identList, $type, $initList);
    }

    private function parseConstSpec(bool $firstInGroup): ConstSpec
    {
        $identList = $this->parseIdentList();
        $type = $this->tryParseType();
        if ($firstInGroup) {
            $this->consume(Token::Eq);
            $initList = $this->parseExprList();
        } elseif ($this->consumeIf(Token::Eq) !== null) {
            $initList = $this->parseExprList();
        } else {
            $initList = null;
        }

        return new ConstSpec($identList, $type, $initList);
    }

    private function parseTypeSpec(bool $_): TypeSpec
    {
        $ident = $this->parseIdent();
        $eq = $this->consumeIf(Token::Eq);

        if ($eq === null) {
            //typedef
            $typeParams = $this->match(Token::LeftBracket) ?
                $this->parseTypeParams() :
                null;
            $type = $this->parseType();
            $value = new TypeDef($ident, $typeParams, $type);
        } else {
            // type alias
            $type = $this->parseType();
            $value = new AliasDecl($ident, Operator::fromLexeme($eq), $type);
        }

        return new TypeSpec($value);
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
        $expr = $this->parseOperandOrType();

        while (true) {
            switch ($this->peek()->token) {
                case Token::LeftBracket:
                    $expr = $this->parseIndexOrSliceExpr($expr);
                    break;
                case Token::LeftParen:
                    $expr = $this->parseCallExpr($expr);
                    break;
                case Token::LeftBrace:
                    if (
                        $expr instanceof Type
                        || (!$this->cfHeader && self::canBeType($expr))
                    ) {
                        $expr = $this->parseCompositeLit($expr);
                    } else {
                        break 2;
                    }
                    break;
                case Token::Dot:
                    $this->advance();
                    $expr = match ($this->peek()->token) {
                        Token::LeftParen => $this->parseTypeAssertionExpr($expr),
                        Token::Ident => $this->parseSelectorExpr($expr),
                        // no break
                        default => $this->error(\sprintf('Unexpected token "%s"', $this->peek()->token->name)),
                    };
                    break;
                default:
                    break 2;
            }
        }

        if (!$expr instanceof PrimaryExpr) {
            $this->error(\sprintf('Expected primary expression, got "%s"', $expr::class));
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
        return $expr instanceof Ident
            || $expr instanceof SelectorExpr;
    }

    private function parseCompositeLit(?Expr $type = null): CompositeLit
    {
        $type = self::tryTypeFromExpr($type);
        if ($type !== null && !$type instanceof Type) {
            $this->error(\sprintf('Composite types expects type expr, got "%s"', $type::class));
        }

        $lBrace = $this->parsePunctuation(Token::LeftBrace);
        $elemList = $this->match(Token::RightBrace) ?
            null :
            $this->parseElementList();
        $rBrace = $this->parsePunctuation(Token::RightBrace);

        return new CompositeLit($type, $lBrace, $elemList, $rBrace);
    }

    private static function tryTypeFromExpr(?Expr $expr): ?Type
    {
        return match (true) {
            $expr instanceof Type => $expr,
            $expr instanceof Ident => new SingleTypeName($expr, null),
            $expr instanceof SelectorExpr => $expr->expr instanceof Ident ?
                new QualifiedTypeName($expr->expr, new SingleTypeName($expr->selector, null)) :
                null,
            default => null,
        };
    }

    private function parseElementList(): ElementList
    {
        $elems = [];
        while (!$this->match(Token::RightBrace)) {
            $elems[] = $this->parseKeyedElement();

            $comma = $this->consumeIf(Token::Comma);

            if ($this->match(Token::RightBrace)) {
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
        $expr = $this->parseElement();

        if (!$this->match(Token::Colon)) {
            return new KeyedElement(null, null, $expr);
        }

        $colon = $this->parsePunctuation(Token::Colon);
        $element = $this->parseElement();

        return new KeyedElement($expr, $colon, $element);
    }

    private function parseElement(): Expr
    {
        return $this->match(Token::LeftBrace) ?
            $this->parseCompositeLit() :
            $this->parseExpr();
    }

    private function parseCallExpr(Expr $expr): CallExpr
    {
        $lParen = $this->parsePunctuation(Token::LeftParen);
        $exprs = [];
        $ellipsis = null;
        $firstArg = true;

        while (!$this->match(Token::RightParen) && $ellipsis === null) {
            if ($firstArg && self::firstArgIsType($expr)) {
                $exprs[] = $this->parseType();
                $firstArg = false;
            } else {
                $exprs[] = $this->parseExpr();
                if ($this->match(Token::Ellipsis)) {
                    $ellipsis = $this->parsePunctuation(Token::Ellipsis);
                }
            }

            $comma = $this->consumeIf(Token::Comma);

            if ($this->match(Token::RightParen)) {
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

    private static function firstArgIsType(Expr $expr): bool
    {
        return $expr instanceof Ident
            && ($expr->name === 'make' || $expr->name === 'new');
    }

    private function parseIndexOrSliceExpr(Expr $expr): IndexExpr|SliceExpr
    {
        $lBrack = $this->parsePunctuation(Token::LeftBracket);
        $indices = [];

        if (!$this->match(Token::Colon)) {
            $indices[] = $this->parseExpr();
        }

        $colons = [];
        $maxColons = 2;
        $i = 0;

        while (\count($colons) < $maxColons && $this->match(Token::Colon)) {
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
                $indices[2] ?? $this->error('Wrong number of indices'),
                $rBrack
            ),
        };
    }

    private function parseOperandOrType(): Operand|Type
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
            default => $this->tryParseType() ??
                $this->error(\sprintf('Unknown token "%s" in operand expression', $this->peek()->token->name)),
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

        // todo weird ellipsis cases
        if (!$this->match(Token::RightParen)) {
            $identsOrTypes = $this->parseTypeOrIdentList();
            $ellipsis = $variadic ? $this->tryParsePunctuation(Token::Ellipsis) : null;
            $type = $this->tryParseType();

            if ($type === null) {
                if (!$identsOrTypes instanceof TypeList) {
                   $this->error('Type list expected');
                }

                $params = \array_map(
                    static fn (Type $type): ParamDecl => new ParamDecl(null, null, $type),
                    $identsOrTypes->types,
                );
            } else {
                if ($identsOrTypes instanceof TypeList) {
                    $identsOrTypes = IdentList::fromTypeList($identsOrTypes);
                }

                $params[] = new ParamDecl($identsOrTypes, $ellipsis, $type);
                $this->consumeIf(Token::Comma);

                while (!$this->match(Token::RightParen)) {
                    $idents = $this->parseIdentList();
                    $ellipsis = $variadic ? $this->tryParsePunctuation(Token::Ellipsis) : null;
                    $type = $this->parseType();
                    $params[] = new ParamDecl($idents, $ellipsis, $type);

                    if (!$this->match(Token::Comma)) {
                        break;
                    }

                    $this->consume(Token::Comma);
                }
            }
        }

        $rParen = $this->parsePunctuation(Token::RightParen);

        return new Params($lParen, $params, $rParen);
    }

    private function parseResult(): Params|Type|null
    {
        return $this->match(Token::LeftParen) ?
            $this->parseParams(false) :
            $this->tryParseType();
    }

    private function tryParseType(): ?Type
    {
        return match ($this->peek()->token) {
            Token::Ident => $this->parseTypeName(),
            Token::Mul => $this->parsePointerType(),
            Token::LeftParen => $this->parseParenType(),
            Token::Func => $this->parseFuncType(),
            Token::LeftBracket => $this->parseArrayOrSliceType(),
            Token::Map => $this->parseMapType(),
            Token::Arrow, Token::Chan => $this->parseChannelType(),
            Token::Struct => $this->parseStructType(),
            Token::Interface => $this->parseInterfaceType(),
            default => null,
        };
    }

    private function parseType(): Type
    {
        return $this->tryParseType() ?? $this->error('Type expected');
    }

    private function parseTypeList(): TypeList
    {
        $types = [];
        do {
            $types[] = $this->parseType();
        } while ($this->consumeIf(Token::Comma) !== null);

        return new TypeList($types);
    }

    private function parseTypeOrIdentList(): TypeList|IdentList
    {
        /** @var Type[] $typeOrIdents */
        $typeOrIdents = [];
        do {
            $typeOrIdent = $this->parseTypeOrIdent();

            if ($typeOrIdent instanceof Ident) {
                $idents = [$typeOrIdent];
                foreach ($typeOrIdents as $type) {
                    $idents[] = $type instanceof SingleTypeName ?
                        $type->name :
                        $this->error(\sprintf('Ident expected, got "%s"', $type::class));
                }

                return new IdentList($idents);
            }

            $typeOrIdents[] = $typeOrIdent;
        } while ($this->consumeIf(Token::Comma) !== null);

        return new TypeList($typeOrIdents);
    }

    private function parseTypeOrIdent(): Type|Ident
    {
        if ($this->match(Token::Ident)) {
            return $this->parseTypeNameOrIdent();
        }

        return $this->parseType();
    }

    private function parseParenType(): ParenType
    {
        return new ParenType(
            $this->parsePunctuation(Token::LeftParen),
            $this->tryParseType() ?? $this->parseKeyword(Token::Type),
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
        while (true) {
            $chan = match ($this->peek()->token) {
                Token::Chan => $this->parseKeyword(Token::Chan),
                Token::Arrow => $this->parseOperator(Token::Arrow),
                default => null,
            };

            if ($chan === null) {
                break;
            }

            $chans[] = $chan;
        }

        return new ChannelType(
            $chans,
            $this->parseType(),
        );
    }

    private function parseInterfaceType(): InterfaceType
    {
        $keyword = $this->parseKeyword(Token::Interface);
        $lBrace = $this->parsePunctuation(Token::LeftBrace);
        $elems = [];

        while (!$this->match(Token::RightBrace)) {
            $typeOrIdent = $this->tryParseType();
            if ($typeOrIdent instanceof SingleTypeName && $this->match(Token::LeftParen)) {
                // method elem
                $ident = new Ident($typeOrIdent->name->pos, $typeOrIdent->name->name);
                $elems[] = $this->parseMethodElem($ident);
            } elseif (!$this->match(Token::Semicolon)) {
                $elem = $this->parseTypeElem();
                if ($typeOrIdent !== null) {
                    $elems[] = new TypeElem([$typeOrIdent, ...$elem->typeTerms]);
                } else {
                    $elems[] = new TypeElem($elem->typeTerms);
                }
            } elseif ($typeOrIdent !== null) {
                $elems[] = new TypeElem([$typeOrIdent]);
            }

            $this->parseSemicolon();
        }

        $rBrace = $this->parsePunctuation(Token::RightBrace);

        return new InterfaceType($keyword, $lBrace, $elems, $rBrace);
    }

    private function parseMethodElem(Ident $ident): MethodElem
    {
        $sign = $this->parseSignature();

        return new MethodElem($ident, $sign);
    }

    private function parseStructType(): StructType
    {
        $keyword = $this->parseKeyword(Token::Struct);
        $lBrace = $this->parsePunctuation(Token::LeftBrace);
        $fields = [];

        while (!$this->match(Token::RightBrace)) {
            $identList = $this->parseIdentList();
            $type = $this->tryParseType();

            $tag = match ($this->peek()->token) {
                Token::String => $this->parseStringLit(),
                Token::RawString => $this->parseRawStringLit(),
                default => null,
            };
            $fields[] = new FieldDecl($identList, $type, $tag);
            $this->parseSemicolon();
        }

        $rBrace = $this->parsePunctuation(Token::RightBrace);

        return new StructType($keyword, $lBrace, $fields, $rBrace);
    }

    private function parseIdentList(): IdentList
    {
        $idents = [];
        do {
            $idents[] = $this->parseIdent();
        } while ($this->consumeIf(Token::Comma) !== null);

        return new IdentList($idents);
    }

    private function parseGroupSpec(SpecType $type): GroupSpec
    {
        $lParen = $this->parsePunctuation(Token::LeftParen);
        /** @var Spec[] $specs */
        $specs = [];
        $parser = $this->matchSpecParser($type);
        $first = true;

        while (!$this->match(Token::RightParen)) {
            $specs[] = $parser($first);
            $this->parseSemicolon();
            $first = false;
        }

        $rParen = $this->parsePunctuation(Token::RightParen);

        return new GroupSpec($lParen, $specs, $rParen, $type);
    }

    private function parseImportSpec(bool $_): ImportSpec
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

    private function tryParseOperator(Token $token): ?Operator
    {
        return $this->match($token) ?
            Operator::fromLexeme($this->consume($token)) :
            null;
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
        $ident = Ident::fromLexeme($this->consume(Token::Ident));

        $typeName = $this->consumeIf(Token::Dot) !== null ?
            Ident::fromLexeme($this->consume(Token::Ident)) :
            null;

        $typeArgs = $this->match(Token::LeftBracket) ?
            $this->parseTypeArgs() :
            null;

        if ($typeArgs !== null && empty($typeArgs)) {
            $this->error('Type arguments cannot be empty');
        }

        return $typeName === null ?
            new SingleTypeName($ident, $typeArgs) :
            new QualifiedTypeName(
                $ident,
                new SingleTypeName($typeName, $typeArgs),
            );
    }

    private function parseTypeNameOrIdent(): TypeName|Ident
    {
        $ident = Ident::fromLexeme($this->consume(Token::Ident));

        $typeName = $this->consumeIf(Token::Dot) !== null ?
            Ident::fromLexeme($this->consume(Token::Ident)) :
            null;

        if ($this->match(Token::LeftBracket)) {
            if (!$this->checkAheadAfter(Token::RightBracket, Token::Comma, Token::RightBrace)) {
                return $ident;
            }

            $typeArgs = $this->parseTypeArgs();
        } else {
            $typeArgs = null;
        }

        return $typeName === null ?
            new SingleTypeName($ident, $typeArgs) :
            new QualifiedTypeName(
                $ident,
                new SingleTypeName($typeName, $typeArgs),
            );
    }

    /**
     * @return TypeList[]
     */
    private function parseTypeArgs(): array
    {
        $this->consume(Token::LeftBracket);
        $args = [];

        while (!$this->match(Token::RightBracket)) {
            $args[] = $this->parseTypeList();

            if (!$this->match(Token::Comma)) {
                break;
            }

            $this->consume(Token::Comma);
        }

        $this->consume(Token::RightBracket);

        return $args;
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

    private function tryParsePunctuation(Token $token): ?Punctuation
    {
        return $this->match($token) ?
            Punctuation::fromLexeme($this->consume($token)) :
            null;
    }

    private function inCfHeader(): void
    {
        $this->cfHeader = true;
    }

    private function outCfHeader(): void
    {
        $this->cfHeader = false;
    }

    private function parseSemicolon(): void
    {
        if (!$this->match(Token::RightBrace, Token::RightBracket)) {
            $this->consume(Token::Semicolon);
        }
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

    private function checkAheadAfter(Token $after, Token ...$needles): bool
    {
        $i = 0;
        while ($this->peekBy($i++)->token !== $after);

        return \in_array($this->peekBy($i)->token, $needles, true);
    }

    private function peekBy(int $by): Lexeme
    {
        while (true) {
            $lexeme = $this->lexemes[$this->cur + $by] ?? null;

            if ($lexeme === null) {
                break;
            }

            if (!self::isToSkip($lexeme)) {
                return $lexeme;
            }

            ++$this->cur;
        }

        throw new \OutOfBoundsException('Cannot peek that far');
    }

    private function recover(ParseMode $mode): void
    {
        while ($this->peek()->token !== Token::Eof) {
            if ($mode === ParseMode::SingleDecl && $this->match(
                Token::Semicolon,
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

            if ($this->match(
                Token::Struct,
                Token::Func,
                Token::Var,
                Token::Const,
                Token::Type,
            )) {
                return;
            }

            $this->advance();
        }
    }

    private static function isToSkip(Lexeme $lexeme): bool
    {
        return $lexeme->token === Token::Comment
            || $lexeme->token === Token::MultilineComment;
    }
}
