<?php

namespace Cent\HStore\Doctrine\Query;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * Class ContainsFunction
 */
class ContainsFunction extends FunctionNode
{
    public $hstore1Expression = null;
    public $hstore2Expression = null;

    /**
     * @param SqlWalker $sqlWalker
     *
     * @return string
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        return 'contains(' .
        $this->hstore1Expression->dispatch($sqlWalker) . ', ' .
        $this->hstore2Expression->dispatch($sqlWalker) .
        ')';
    }

    /**
     * @param Parser $parser
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->hstore1Expression = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->hstore2Expression = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
