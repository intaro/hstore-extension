<?php

namespace Cent\HStore\Doctrine\Query;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * Class HstoreDifferenceFunction
 */
class HstoreDifferenceFunction extends FunctionNode
{
    public $hstoreExpression = null;
    public $keyExpression = null;

    /**
     * @param SqlWalker $sqlWalker
     *
     * @return string
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        return  $this->hstoreExpression->dispatch($sqlWalker) . ' - ARRAY[' .
        $this->keyExpression->dispatch($sqlWalker) . ']';
    }

    /**
     * @param Parser $parser
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->hstoreExpression = $parser->StringPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->keyExpression = $parser->StringPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
