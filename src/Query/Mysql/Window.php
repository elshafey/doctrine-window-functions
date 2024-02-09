<?php

namespace Elshafey\DoctrineExtensions\WindowFunctions\Query\Mysql;

use Doctrine\ORM\Query\AST\Functions\FunctionNode,
    Doctrine\ORM\Query\Lexer;

/**
 * 
 * 
 * @author Mohamed Elshafey <https://github.com/elshafey>
 */
class Window extends FunctionNode
{
    protected $isNativeWindowFuction = false;
    protected $nativeFunction;
    protected $nativeFunctionArg;
    protected $aggregateFunction;
    protected $partitionByFields=[];
    protected $orderBy=[];

    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $lexer = $parser->getLexer();

        if ($lexer->isNextToken(Lexer::T_IDENTIFIER)) {
            $this->isNativeWindowFuction = true;
            $this->nativeFunction = $lexer->lookahead->value;
            $parser->match(Lexer::T_IDENTIFIER); // to function name
            $parser->match(Lexer::T_OPEN_PARENTHESIS);
            if (!$lexer->isNextToken(Lexer::T_CLOSE_PARENTHESIS)) {
                $this->nativeFunctionArg = $parser->StringPrimary();
            }
            $parser->match(Lexer::T_CLOSE_PARENTHESIS);
        } else {
            $this->aggregateFunction = $parser->StringPrimary();
        }
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
        if ($lexer->isNextToken(Lexer::T_IDENTIFIER) && strtolower($lexer->lookahead->value) === 'over') {
            // $this->overPart = $parser->StringPrimary();
            $this->parseOver($parser);
        } else {
            $parser->syntaxError('OVER');
        }
    }

    /**
     * Parsing the DQL port of OVER()
     * 
     * @return void
     */
    public function parseOver(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $lexer = $parser->getLexer();
        if($lexer->isNextToken(Lexer::T_IDENTIFIER)&&strtolower($lexer->lookahead->value)==='partition'){
            // $this->partitionByClause=true;
            $this->partitionByFields=$this->parseGrouping($parser,Lexer::T_IDENTIFIER);
        }
        if ($lexer->isNextToken(Lexer::T_ORDER)) {
            
            $this->orderBy = $this->parseGrouping($parser,Lexer::T_ORDER);
        }
        
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
        
    }
    
    protected function parseGrouping(\Doctrine\ORM\Query\Parser $parser,$math){
        $lexer = $parser->getLexer();
        $orderBy=[];
        $parser->match($math);
        if($lexer->isNextToken(Lexer::T_BY)){
            $parser->match(Lexer::T_IDENTIFIER);//to match `BY`
        }else{
            $parser->syntaxError('BY'); 
        }
        
        $orderBy[]=$this->extractGroupingField($parser);

        while ($lexer->isNextToken(Lexer::T_COMMA)) {
            $parser->match(Lexer::T_COMMA);
            $orderBy[] = $this->extractGroupingField($parser);
        }

        return $orderBy;
    }

    protected function extractGroupingField(\Doctrine\ORM\Query\Parser $parser){
        $lexer = $parser->getLexer();
        $openedParenthesis=0;
        if($lexer->isNextToken(Lexer::T_OPEN_PARENTHESIS)){
            while ($lexer->isNextToken(Lexer::T_OPEN_PARENTHESIS)) {
                $parser->match(Lexer::T_OPEN_PARENTHESIS);
                $openedParenthesis++;
            }
        }
        $field=[   
            'field'=>$parser->StringPrimary(),
            'direction'=> $lexer->isNextToken(Lexer::T_ASC)? 'ASC':($lexer->isNextToken(Lexer::T_DESC)? 'DESC':'')
        ];
        if($lexer->isNextToken(Lexer::T_CLOSE_PARENTHESIS)){
            while ($lexer->isNextToken(Lexer::T_CLOSE_PARENTHESIS)&&$openedParenthesis>0) {
                $parser->match(Lexer::T_CLOSE_PARENTHESIS);
                $openedParenthesis--;
            }
        }
        if($lexer->isNextToken(Lexer::T_ASC)){
            $parser->match(Lexer::T_ASC);
        }elseif($lexer->isNextToken(Lexer::T_DESC)){
            $parser->match(Lexer::T_DESC);
        }

        return $field;
    }

    public function getOverSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return 'OVER('.$this->getGroupingSql($sqlWalker,$this->partitionByFields,' PARTITION BY').' '.
        $this->getGroupingSql($sqlWalker,$this->orderBy,' ORDER BY').')';
    }

    protected function getGroupingSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker,array $fieldsList,$keyword){
        $groupingBy='';
        if($fieldsList){
            foreach ($fieldsList as  $pathExp) {
                $orderByFields[]=$pathExp['field']->dispatch($sqlWalker).' '.($pathExp['direction']?? '');
            }
            $groupingBy=$keyword.' '.implode(',',$orderByFields);
        };

        return $groupingBy;
    }

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        $over = $this->getOverSql($sqlWalker);

        if ($this->isNativeWindowFuction) {
            $function = $this->nativeFunction . '(' . ($this->nativeFunctionArg ? $this->nativeFunctionArg->dispatch($sqlWalker) : '') . ')';
        } else {
            $function = $this->aggregateFunction->dispatch($sqlWalker);
        }
        return $function . ' ' . $over;
    }
}
