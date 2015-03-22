<?php

namespace Intaro\HStore\Doctrine\Query;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;

class FetchvalFunction extends FunctionNode
{
    public $hstoreExpression = null;
    public $keyExpression = null;

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return 'fetchval('.
            $this->hstoreExpression->dispatch($sqlWalker) . ', ' .
            $this->keyExpression->dispatch($sqlWalker) . ')';
    }

    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->hstoreExpression = $parser->StringPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->keyExpression = $parser->StringPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
