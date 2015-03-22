<?php

namespace Intaro\HStore\Doctrine\Query;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;

class ContainsFunction extends FunctionNode
{
    public $hstore1Expression = null;
    public $hstore2Expression = null;

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return 'contains(' .
            $this->hstore1Expression->dispatch($sqlWalker) . ', ' .
            $this->hstore2Expression->dispatch($sqlWalker) .
        ')';
    }

    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->hstore1Expression = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->hstore2Expression = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
