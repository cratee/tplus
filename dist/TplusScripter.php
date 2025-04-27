<?php
/**
    ------------------------------------------------------------------------------
    Tplus 1.0.4 
    Released 2024-12-31

    
    The MIT License (MIT)
    
    Copyright: (C) 2023 Hyeonggil Park

    Permission is hereby granted, free of charge, to any person obtaining a copy
    of this software and associated documentation files (the "Software"), to deal
    in the Software without restriction, including without limitation the rights
    to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    copies of the Software, and to permit persons to whom the Software is
    furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in all
    copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
    SOFTWARE.
    ------------------------------------------------------------------------------
*/

namespace Tplus;

class Scripter {
    
    private static $currentLine = 1;

    public static $wrapper;
    public static $loopHelper;
    public static $userCode;

    public static function script($htmlPath, $scriptPath, $sizePad, $header, $config) {
    
        self::$valWrapper = '\\'.(empty($config['ValWrapper']) ? 'TplValWrapper' : $config['ValWrapper']);
        self::$loopHelper = '\\'.(empty($config['LoopHelper']) ? 'TplLoopHelper' : $config['LoopHelper']);
        try {
            self::$userCode = self::getHtml($htmlPath);        
            self::saveScript($config['HtmlScriptRoot'], $scriptPath, $sizePad, $header, self::parse()); 

        } catch(SyntaxError $e) {
            self::reportError('Tplus Syntax Error: ', $e->getMessage(), $htmlPath, self::$currentLine);

        } catch(FatalError $e) {
            self::reportError('Tplus Scripter Fatal Error: ', $e->getMessage(), $htmlPath, self::$currentLine);
        }
    }

    private static function reportError($title, $message, $htmlPath, $currentLine)
    {
        $htmlPath = realpath($htmlPath);
        if (ini_get('log_errors')) {
            error_log($title. $message.' in '.$htmlPath.' on line '.$currentLine);
        }
        if (ini_get('display_errors')) {
            echo '<b>'.$title.'</b>'.htmlspecialchars($message).' in <b>'.$htmlPath.'</b> on line <b>'.$currentLine.'</b>';
            // ? ob_end_flush();
        }
        exit;
    }

    private static function saveScript($scriptRoot, $scriptPath, $sizePad, $header, $script) {
        $scriptRoot = preg_replace(['~\\\\+~','~/$~'], ['/',''], $scriptRoot);

        if (!is_dir($scriptRoot)) {
            throw new FatalError('script root '.$scriptRoot.' does not exist');
        }
        if (!is_readable($scriptRoot)) {
            throw new FatalError('script root '.$scriptRoot.' is not readable. check read-permission of web server.');
        }
        if (substr(__FILE__,0,1)==='/' and !is_writable($scriptRoot)) {
            //@note is_writable() might not work on some OS(old version Windows?).
            throw new FatalError('script root '.$scriptRoot.' is not writable. check write-permission of web server.');
        }

        $filePerms  = fileperms($scriptRoot);
        $scriptPath = preg_replace('~[/\\\\]+~', '/', $scriptPath);
        $scriptRelPath  = substr($scriptPath, strlen($scriptRoot)+1);
        $scriptRelPathParts = explode('/', $scriptRelPath);
        $filename = array_pop($scriptRelPathParts);
        $path = $scriptRoot;

        foreach ($scriptRelPathParts as $dir) {
            $path .= '/'.$dir;
            if (!is_dir($path)) {
                if (!mkdir($path, $filePerms)) {
                    throw new FatalError('fail to create directory '.$path.' check the write-permission.');
                }
            }
        }

        $headerPostfix = ' */ ?>'."\n";
        $headerSize = strlen($header) + $sizePad + strlen($headerPostfix);
        $scriptSize = $headerSize + strlen($script);
        $header .= str_pad((string)$scriptSize, $sizePad, '0', STR_PAD_LEFT) . $headerPostfix;
        $script = $header . $script;
        $scriptFile = $path.'/'.$filename;

        if (!file_put_contents($scriptFile, $script, LOCK_EX)) {
            throw new FatalError('fail to write file '.$scriptFile.' check permission or unknown problem.');
        }
        if (!chmod($path.'/'.$filename, $filePerms)) {
            throw new FatalError('fail to set permission of file '.$scriptFile.' check permission or unknown problem.');
        }
    }

    public static function decreaseUserCode($parsedUserCode) {
        self::$userCode = substr(self::$userCode, strlen($parsedUserCode));
        self::$currentLine += substr_count($parsedUserCode,"\n");
    }

    public static function valWrapperMethods() {
        return self::getMethods(self::$valWrapper);
    }
    public static function loopHelperMethods() {
        return self::getMethods(self::$loopHelper);
    }
    private static function getMethods($class) {
        static $methods = [];
        if (empty($methods[$class])) {
            $methods[$class] = [];
            if (!class_exists($class)) {
                //@todo "There is no ... class $class which contains method ....()"
                throw new FatalError('--- class "'.substr($class, 1).'" does not exist.');
            }
            $reflectionMethods = (new \ReflectionClass($class))->getMethods(\ReflectionMethod::IS_PUBLIC);
            foreach ($reflectionMethods as $m) {
                if (!$m->isStatic()) {
                    $methods[$class][] = strtolower($m->name);
                }
            }
        }
        return $methods[$class];
    }

    
    private static function parse() {
        $foundScriptTag = self::findScriptTag();
        if ($foundScriptTag) {
            throw new SyntaxError('PHP tag not allowed. <b>'.$foundScriptTag.'</b>');
        };

        $resultScript='';
        while (self::$userCode) {
            /*$pattern =
            '~              // $parsedUserCode ($matches[0])
                (.*?)       // $betweenTags
                (<!--\s*)?
                (\[)
                (\\\\*)
                ([=@?:/*])
            ~xs';*/
            [$parsedUserCode, $betweenTags, $htmlLeftCmnt, $leftTag, $escape, $command]
                = self::findLeftTagAndCommand(self::$userCode);
        
            $resultScript .= $betweenTags;

            self::decreaseUserCode($parsedUserCode);

            if (!$leftTag) { 
                break;
            }

            if ($escape) {
                $resultScript .= $htmlLeftCmnt.$leftTag.substr($escape, 1).$command;

            } else if ('*' === $command) {
                $comment = self::getComment(self::$userCode);
                self::decreaseUserCode($comment);

            } else {
                $statement = Statement::script($command);
                if (false === $statement) {
                    // [:][/] out of [@] or [?] blocks.
                    $resultScript .= $htmlLeftCmnt.$leftTag./*$escape.*/$command;
                } else {
                    $resultScript .= $statement;
                }
            }            
        }

        for ($commandStack = Statement::commandStack();; ) {
            $command = $commandStack->pop();
            if (!$command or in_array($command, ['@', '?'])) {
                break;
            }
        }
        if ($command) {
            throw new SyntaxError('['.$command.'...] is not closed by [/]');
        }

        return $resultScript;
    }

    private static function getHtml($htmlPath) {
        $html = file_get_contents($htmlPath);
        
        // remove UTF-8 BOM
        $html = str_replace("\xEF\xBB\xBF", '', $html);

        // set to unix new lines
        return str_replace(["\r\n", "\r"], "\n", $html);
    }

    private static function findScriptTag() {
        $scriptTagPattern = ini_get('short_open_tag') ? '~(<\?)~i' : '~(<\?(php\s|=))~i';
        // @note <% and <script language=php> removed since php 7.0
        // @todo check short_open_tag if whitespace is mandatory after <?  in v5.x.

        $split = preg_split(
            $scriptTagPattern,
            self::$userCode,
            2, 
            PREG_SPLIT_DELIM_CAPTURE
        );

        if (1 < count($split)) {
            self::$currentLine += substr_count($split[0], "\n");
            $foundScriptTag = $split[1];
            return $foundScriptTag;
        }
        return '';
    }

    private static function findLeftTagAndCommand($userCode) {
        $pattern =
        '~
            (.*?)
            (<!--\s*)?
            (\[)
            (\\\\*)
            ([=@?:/*])
        ~xs';

        if (preg_match($pattern, $userCode, $matches)) {
            return $matches; 
        }

        return [$userCode, $userCode, '', '', '', ''];
    }

    private static function getComment($userCode) {
        $pattern =
        '~  
            ^.*?
            \*\]
            (?:\s*-->)?
        ~xs';
        
        return preg_match($pattern, $userCode, $matches)
            ? $matches[0]
            : $userCode;
    }
}

class SyntaxError extends \Exception {}
class FatalError extends \Exception {}

class Stack {
    protected $items = [];

    public function peek() {
        return end($this->items);
    }
	public function isEmpty() {
		return empty($this->items);
	}
	public function count() {
		return  (func_num_args() > 0) 
            ? array_count_values($this->items)[func_get_arg(0)]
            : count($this->items) 
        ;
	}
    public function push($item) {
        $this->items[] = $item;
    }
    public function pop() {
        return array_pop($this->items);
    }
}



class Statement {
    /**
        $commandStack's items
            ?   (if)
            :   (else)
            @   (loop)
            @:  (loop else)
        //  (else if) not needed for syntax check
    */
    private static $commandStack;

    public static function script($command) {
    
        if (!isset(self::$commandStack)) {
            self::$commandStack = new Stack;
        }

        if ($command === '=') {
            return self::parseEcho();
        }
        switch($command) {
            case '@': $script = self::parseLoop();      break;
            case '?': $script = self::parseBranch();    break;
            case '/': 
                if (!self::$commandStack->peek()) {
                    return false;
                }
                $script = self::parseEnd();             break;
            case ':':
                $prevCommand = self::$commandStack->peek();
                if (!$prevCommand) {
                    return false;
                }
                if (in_array($prevCommand, [':', '@:'])) {     
                    throw new SyntaxError("Unexpected ':' command");
                }
                switch($prevCommand[0]) {                    
                    case '?': $script = self::parseElse();      break;
                    case '@': $script = self::parseLoopElse();  break;
                }
        }

        self::parseRightTag();
        return '<?php '.$script.' ?>';
    }
    public static function commandStack() {
        return self::$commandStack;
    }

    private static function parseEcho() {
        $expression = Expression::script();
        self::parseRightTag();
        return '<?= '.$expression.' ?>';
    }

    private static function parseEnd() {
        /*if (self::$commandStack->isEmpty()) {
            throw new SyntaxError('Unexpected end tag [/]');
        }*/
        $script = ('@' === self::$commandStack->peek()) ? '}}' : '}';
        //while(!in_array(self::$commandStack->pop(), ['@', '?', '$']));
        while(!in_array(self::$commandStack->pop(), ['@', '?']));
        return $script;
    }
    private static function parseRightTag() {
        $pattern = 
        // @note pcre modifier 'x' means that white-spaces in pattern are ignored.
        // @note pcre modifier 's' means that dot(.) contains newline.
        '~  
            ^\s*
            \]
            (?:\s*-->)?
        ~xs';
        if (!preg_match($pattern, Scripter::$userCode, $matches)) {
            throw new SyntaxError('Tag not correctly closed.');
        }
    
        Scripter::decreaseUserCode($matches[0]);
    }

    private static function parseLoop() {
        self::$commandStack->push('@');
        $expressionScript = Expression::script();


        ['a'=>$a, 'i'=>$i, 's'=>$s, 'k'=>$k, 'v'=>$v] = self::loopNames(self::loopDepth());

        return $a.'='.$expressionScript.';'
            .'if (is_array('.$a.') and !empty('.$a.')) {'
                .$s.'=count('.$a.');'
                .$i.'=-1;'
                .'foreach('.$a.' as '.$k.'=>'.$v.') {'
                    .' ++'.$i.';';
    }

    private static function parseBranch() {
        $expressionScript = Expression::script();
        self::$commandStack->push('?');
        $statementScript = 'if ('.$expressionScript.') {';

        return $statementScript; 
    }


    private static function expressionExists() {
        return !preg_match('/^\s*\]/', Scripter::$userCode);
    }

    private static function parseLoopElse() {
        self::$commandStack->push('@:');
        return '}} else {';
    }
    private static function parseElse() {
        if (self::expressionExists()) {
            return '} else if ('.Expression::script().') {';
        }

        self::$commandStack->push(':');    // else
        return '} else {';
    }

    public static function loopName($depth, $keyword='') {
        return self::loopNames($depth)[$keyword];
    }
    public static function loopNames($depth) {
        static $names=[];
        if (empty($names[$depth])) {
            $name = '$L'.$depth;  // $L1, $L1i, $L2i, $L1s, ...
            $names[$depth] = ['a'=>$name, 'i'=>$name.'i', 's'=>$name.'s', 'k'=>$name.'k', 'v'=>$name.'v'];
        }
        return $names[$depth];
    }
    public static function loopDepth() {
        return self::$commandStack->count('@');
    }
}

class Token {
    const SPACE     = 0;
    const DOT       = 1;
    const OPERAND   = 2;
    const OPERATOR  = 4;
    const DELIMITER = 8;
    const OPEN      = 16;
    const CLOSE     = 32;
    const UNARY     = 64;
    const BI_UNARY  = 128;

    const CXT       = self::OPEN | self::DELIMITER;
    const WRAP_SET  = self::OPEN | self::DOT | self::OPERAND;
    const WRAP_UNSET= self::OPERATOR;

    //const KEYWORD   = ['true', 'false', 'null', 'this'];

    const GROUPS = [
        self::SPACE => [
            'Space'  =>'\s+'
        ],
        self::DOT => [
            'Dot'   =>'\.+'
        ],
        self::OPERAND => [
            'Name'      =>'[\p{L}_][\p{L}\p{N}_]*',
            'Number'    =>'(?:\d+(?:\.\d*)?)(?:[eE][+\-]\d+)?',
            'Quoted'    =>'(?:"(?:\\\\.|[^"])*")|(?:\'(?:\\\\.|[^\'])*\')',
        ],
        self::OPERATOR => [
            'Xcrement'  => '\+\+|--',
            'Comparison'=> '===?|!==?|<=?|>=?',       //@todo check a == b == c
            'Logic'     => '&&|\|\|',                   
            'Elvis'     => '\?:|\?\?',
            'ArithOrBit'=> '[%*/&|\^]|<<|>>',         //@todo check quoted
        ],
        self::DELIMITER => [           // to create new expression
            'TernaryIf' => '\?',                        
            'TernaryElseOrKVDelim'=>':',                 
            'Comma'     => ',',
        ],
        self::OPEN => [
            'ParenthesisOpen'=>'\(',
            'BraceOpen'=>'\{',
            'BracketOpen'=>'\[',        
        ],
        self::CLOSE => [
            'ParenthesisClose'=>'\)',
            'BraceClose'=>'\}',
            'BracketClose'=>'\]',   
        ],
        self::UNARY => [
            'Unary' =>'~|!',
        ],
        self::BI_UNARY => [
            'Plus'  => '\+',
            'Minus' => '-'
        ]
    ];
}

class Cxt {		// Child Expression Trigger
	const ICE = 1<<0;			//	{ brace for array indexer
	const IKT = 1<<1;			//	[ bracket for array indexer
	const JCE = 1<<2;			//	{ brace for JSON
	const JKT = 1<<3;			//	[ bracket for JSON
	const PAR = 1<<4;			//  (
	const FUN = 1<<5;			//  function(
	const TRN = 1<<6;			//	?

	const JCE_COMMA = 1<<7;
	const JCE_COLON = 1<<8;
	const JKT_COMMA = 1<<9;
	const JKT_COLON = 1<<10;
	const FUN_COMMA = 1<<11;
	const TRN_COLON = 1<<12;

    private static $expression;

    public static function get($prevToken, $currToken, $prevCxt) {
        switch ($currToken['value']) {
            case '{': return $prevToken['name']=='Name' ? self::ICE : self::JCE;
            case '[': return $prevToken['name']=='Name' ? self::IKT : self::JKT;
            case '(': return $prevToken['name']=='Name' ? self::FUN : self::PAR;
            case '?': return self::ternary($prevToken, $currToken, $prevCxt);
            case ',': return self::comma($prevCxt);
            case ':': return self::colon($prevCxt);
        }
    }
    private static function comma($prevCxt) {
        switch ($prevCxt) {
            case self::JCE : return self::JCE_COMMA;
            case self::JKT : return self::JKT_COMMA;
            case self::FUN : return self::FUN_COMMA;
        }
        throw new SyntaxError('[018]Invalid token: ","');
    }
    private static function colon($prevCxt) {
        switch ($prevCxt) {
            case self::JCE : return self::JCE_COLON;
            case self::JKT : return self::JKT_COLON;
            case self::TRN : return self::TRN_COLON;
        }
        throw new SyntaxError('[017]Invalid token: ":"');
    }
    private static function ternary($prevToken, $currToken, $prevCxt) {
        if ($prevToken['group'] & (Token::OPERAND | Token::CLOSE) and
            !($prevCxt & (self::TRN | self::TRN_COLON))) {
            return self::TRN;
        }
        throw new SyntaxError('[016]Invalid token: "?"');
    }

    public static function isEndOf($prevCxt, $stopCode, $expression) {

        // @note  {}, [] and func() allow empty expressions.

        $s = $stopCode;
        self::$expression = $expression;
    
        if ($prevCxt === self::FUN) {
            if ($s === ')') {
                return true;
            }
            if ($s === ',') {
                self::preventEmptryExpression($s);
                return true;
            }

        } else if ($prevCxt === self::FUN_COMMA) {
            if ($s === ',' || $s === ')') {
                self::preventEmptryExpression($s);
                return true;
            }

        } else if ($prevCxt === self::PAR) {
            if ($s === ')') {
                self::preventEmptryExpression($s);
                return true;
            }

        } else if ($prevCxt === self::ICE) {
            if ($s === '}') {
                self::preventEmptryExpression($s);
                return true;
            }

        } else if ($prevCxt === self::IKT) {
            if ($s === ']') {
                self::preventEmptryExpression($s);
                return true;
            }

        } else if ($prevCxt === self::JCE) {
            if ($s === '}') {
                return true;
            }

            if ($s === ':' || $s === ',') {
                self::preventEmptryExpression($s);
                return true;
            }
            
        } if ($prevCxt === self::JKT) {
            if ($s === ']') {
                return true;
            }

            if ($s === ':' || $s === ',') {
                self::preventEmptryExpression($s);
                return true;
            }

        } else if ($prevCxt === self::JCE_COMMA) {
            if ( $s === ',' || $s === ':' || $s === '}') {
                self::preventEmptryExpression($s);
                return true;
            }

        } else if ($prevCxt === self::JKT_COMMA) {
            if ($s === ',' || $s === ':' || $s === ']') {
                self::preventEmptryExpression($s);
                return true;
            }

        } else if ($prevCxt === self::JCE_COLON) {
            if ($s === ',' || $s === '}') {
                self::preventEmptryExpression($s);
                return true;
            }
        
        } else if ($prevCxt === self::JKT_COLON) {
            if ($s === ',' || $s === '}') {
                self::preventEmptryExpression($s);
                return true;
            }

        } else if ($prevCxt === self::TRN) {
            if ($s === ':') {
                self::preventEmptryExpression($s);
                return true;
            }

        } else if ($prevCxt === self::TRN_COLON) {
            if (in_array($s, [',', ')', ']', '}'])) {
                self::preventEmptryExpression($s);
                return true;
            }
        }
    
        throw new SyntaxError('[014]Unexpected token '.$s);
    }
    private static function preventEmptryExpression($s) {
        self::$expression->preventEmptryExpression(
            "[013]Unexpected token '{$s}': missing expression before '{$s}'"
        );
    }
}

class Expression {

    private $cxt = 0;
    private $scriptPieces = [];
    private $wrapperStartIndex = -1;
    private $wrapperDetermined = false;
    

    public static function script() {

        $expression = new Expression();
        $expression->parse();
        return $expression->assembleScriptPieces();
    }

    public function preventEmptryExpression($message) {
        if (empty($this->scriptPieces)) {
            throw new SyntaxError($message);
        }
    }

    private function assembleScriptPieces() {
        return implode('', $this->scriptPieces);
    }

    private function parse() {

        $prevToken = ['group'=>0, 'name'=>'', 'value'=>''];
        NameDotChain::init();
        
        while ($currToken = $this->nextToken()) {
            if ($currToken['group'] === Token::SPACE) {
                continue;
            }

            if ($this->isFinished($prevToken, $currToken)) {
                break;
            }

            $this->normalizeTokenGroup($prevToken, $currToken);
            $this->checkTokensOrder($prevToken, $currToken);
            $this->setWrapperStartIndex($currToken);
            $this->scriptPieces[] = $this->{'parse'.$currToken['name']}($prevToken, $currToken);
                
            if ($currToken['group'] & Token::CXT) {
                $this->cxt = Cxt::get($prevToken, $currToken, $this->cxt);
                $currToken = ['group'=> Token::OPERAND, 'name'=>'Cx', 'value'=>self::script()];
                $this->scriptPieces[] = $currToken['value'];  
            }
            
            $prevToken = $currToken;
        }
    }
 
    private function nextToken() {
        foreach (Token::GROUPS as $tokenGroup => $tokenNames) {
            foreach ($tokenNames as $tokenName => $pattern) {
                if (preg_match('#^('.$pattern.')#s', Scripter::$userCode, $matches)) {
                    Scripter::decreaseUserCode($matches[0]);
                    return ['group' => $tokenGroup, 'name' => $tokenName, 'value' => $matches[0]];
                }
            }
        }
        throw new SyntaxError('[013]Invalid expression: '.substr(Scripter::$userCode,0,10).' ...');
    }

    private function isFinished($prevToken, $currToken) {
        if (! Scripter::$userCode ) {
            throw new SyntaxError('[015]HTML file ends without Tplus closing tag "]".');
        }
        

        $stopCode = substr(Scripter::$userCode, 0, 1);

        if (!in_array($stopCode, [')','}',']','?',':',','])) {
            return false;
        }


        if ($this->ternaryCxt == Cxt::TRN) {
            throw new SyntaxError('[016]missing colon in ternary operator ? : ');
        }

        return Cxt::isEndOf($this->cxt, $stopCode, $this);
    }
 
    private function normalizeTokenGroup($prevToken, &$currToken) {
        if ($currToken['group'] != Token::BI_UNARY) {
            return;
        }
        if ($prevToken['group'] & (Token::OPERAND|Token::CLOSE)) {
            $currToken['group'] = Token::OPERATOR;

        } else {
            $currToken['group'] = Token::UNARY;
        }
    }
 
    private function checkTokensOrder($prevToken, $currToken) {
        if ($prevToken['group'] & (Token::OPERAND|Token::CLOSE) 
            and $currToken['group'] & (Token::OPERAND|Token::UNARY) ) {
            throw new SyntaxError("[012]Unexpected {$prevToken['value']} {$currToken['value']}");
        }
        if ((!$prevToken['group'] || $prevToken['group'] & (Token::DOT|Token::OPERATOR|Token::DELIMITER|Token::UNARY))
            and $currToken['group'] & (Token::OPERATOR|Token::DELIMITER|Token::CLOSE) ) {
            throw new SyntaxError("[011]Unexpected {$prevToken['value']} {$currToken['value']}");
        }
        if ($prevToken['group'] === Token::DOT and $currToken['name'] !== 'Name') {
            throw new SyntaxError("[010]Unexpected {$prevToken['value']} {$currToken['value']}");
        }
        if ($prevToken['group'] === Token::UNARY and $currToken['group'] === Token::UNARY) {
            throw new SyntaxError("[019]Unexpected {$prevToken['value']}{$currToken['value']} Unary operator chaining is not allowed.");
        }
    }

    private function setWrapperStartIndex($currToken) {
        if ($this->wrapperStartIndex === -1 ) {
            if ($currToken['group'] & (Token::WRAP_SET)) {
                $this->wrapperStartIndex = count($this->scriptPieces);
            }
        } else {
            if ($currToken['group'] & (Token::WRAP_UNSET)) {
                $this->wrapperStartIndex = -1;
            }
        }
    }
 
    public function insertValWrapper() {
        if ($this->wrapperStartIndex === -1) {
            throw new FatalError('[003]TplusScripter internal bug: fails to parse val wrapper method.');
        }
        array_splice($this->scriptPieces, $this->wrapperStartIndex, 0, Scripter::$valWrapper.'::_o(');
    }

    private function parseParenthesisOpen($prevToken, $currToken) {
        return $currToken['value'];
    }
    private function parseBraceOpen($prevToken, $currToken) {
        return $currToken['value'];
    }
    private function parseBracketOpen($prevToken, $currToken) {        
        return $currToken['value'];
    }
    private function parseTernaryIf($prevToken, $currToken) {        
        return $currToken['value'];
    }

    private function parseTernaryElseOrKVDelim($prevToken, $currToken) {        
        if ($this->cxt === Cxt::TRN) {
            return ':';
        }
        if ($this->cxt & (Cxt::JCE | Cxt::JKT)) {
            return '=>';
        }
        // @note The colon's validity has already been checked in Cxt::colon()
    }

    private function parseParenthesisClose($prevToken, $currToken) {
        $this->chekcClose($currToken, Cxt::PAR|Cxt::FUN|Cxt::FUN_COMMA);
        return $currToken['value'];
    }
    private function parseBraceClose($prevToken, $currToken) {
        $this->chekcClose($currToken, Cxt::ICE|Cxt::JCE|Cxt::JCE_COMMA|Cxt::JCE_COLON);
        return $currToken['value'];
    }
    private function parseBracketClose($prevToken, $currToken) {
        $this->chekcClose($currToken, Cxt::IKT|Cxt::JKT|Cxt::JKT_COMMA|Cxt::JKT_COLON);
        return $currToken['value'];
    }
    private function checkClose($currToken, $cxt) {
        if (!($this->cxt & $cxt)) {
            throw new SyntaxError('[006]Unexpected '.$currToken['value']);
        }
    }

    private function parseComma($prevToken, $currToken) {
        return $currToken['value'];
    }
 
    private function parseNumber($prevToken, $currToken) {
        return $currToken['value'];
    }

    private function parseQuoted($prevToken, $currToken) {
        if ($prevToken['group']===Token::UNARY) {
            // @policy: String literal cannot follows unary operators.            
            throw new SyntaxError('[001]Unexpected '.$currToken['value']);
        }
        if ($prevToken['name'] == 'Plus') {
            // @policy: Plus operator before string literal is changed to concat operator.
            array_pop( $this->scriptPieces );
            array_push($this->scriptPieces, '.');
        }
        return $currToken['value'];
    }

    private function parseUnary($prevToken, $currToken) {
        return $currToken['value'];
    }

    private function parsePlus($prevToken, $currToken) {
        return ($prevToken['name'] == 'Quoted') ? '.' : ' +';
    }
    private function parseMinus($prevToken, $currToken) {
        return $currToken['value'];
    }

    private function parseXcrement($prevToken, $currToken) {
        throw new SyntaxError('[020]Increment ++ or decrement -- operators are not allowd.');
    }
    private function parseComparison($prevToken, $currToken) {
        return $currToken['value'];  // === == !== != < > <= >=     @todo check a == b == c
    }
    private function parseLogic($prevToken, $currToken) {
        return $currToken['value'];  // && \\
    }
    private function parseElvis($prevToken, $currToken) {
        return $currToken['value'];  // ?: ??
    }
    private function parseArithOrBit($prevToken, $currToken) {
        return $currToken['value'];  // % * / & ^ << >>              @todo check if operand is string
    }

    private function parseDot($prevToken, $currToken) {

        if ($prevToken['group']===Token::CLOSE
            or $prevToken['group']===Token::OPERAND && $prevToken['name']!=='Name') {

            $this->wrapperDetermined = true;
            return;
        }

        NameDotChain::addDot($currToken['value']);
    }
    private function parseName($prevToken, $currToken) { // variable, function, object, method, array, key, namespace, class
        if ($this->wrapperDetermined) {
            NameDotChain::confirmWrapper($currToken['value']);
            $this->insertValWrapper();
            $this->wrapperDetermined = false;
            return ')->'.$currToken['value'];
        }

        return NameDotChain::addName($currToken['value'], $this);
    }
}


class NameDotChain {
    private static $tokens;
    private static $names;
    private static $expression;
    
    public static function init() {
        self::$tokens = [];
        self::$names = [];
    }

    public static function confirmWrapper($name) {
        if (!self::isFunc()) {
            throw new SyntaxError("[007]unexpected {$name}");
        }
        if (!self::isWrapper()) {
            throw new SyntaxError("[007]Wrapper method {$name}() not found in class ".Scripter::$valWrapper);
        }
    }

    public static function addDot($token) {
        if (!self::isEmpty() and strlen($token) > 1) { //  multiple dots after Name. e.g) 'abc..' 'xyz...'
            throw new SyntaxError('[008]Unexpected '.implode('', self::$tokens).$token);
        }
        self::$tokens[] = $token;
    }
    public static function addName($token, $expression) {
        if (in_array($token, ['true', 'false', 'null', 'this'])) {
            if (!self::isEmpty() or self::isFunc()) {
                throw new SyntaxError("[021]'{$token}' is reserved word.");
            }
            if ($token !== 'this') { 
                if (self::isNextDot()) {
                    throw new SyntaxError("[022]'{$token}' is reserved word.");
                }
                return $token;
            }
        }

        self::$tokens[] = $token;
        self::$names[] = $token;

        self::$expression = $expression;

        if (self::isNextDot()) { // chain not ends.
            return;
        }

        if (preg_match('/\.+/', self::$tokens[0])) {
            return self::parseLoopMember();
        }

        if ('this' == self::$names[0]) {
            return self::parseThis();
        }

        if (in_array(self::$names[0], ['GET','SERVER','COOKIE','SESSION','GLOBALS'])) {
            return self::parseAutoGlobals();
        }

        if ($script = self::parseStatic()) {
            return $script;
        }
        return self::parseArray(self::$names);
    }

    private static function isEmpty() {
        return empty(self::$tokens);
    }
    private static function isNextDot() { //Chain not ends.
        return preg_match('/^\s*\./', Scripter::$userCode);
    }
    public static function isFunc() {
        return preg_match('/^\s*\(/', Scripter::$userCode);
    }
    private static function isWrapper($method) {
        return in_array(strtolower($method), Scripter::valWrapperMethods());
    }

    private static function parseAutoGlobals() {

        $names = self::$names;
        
        $global = array_shift($names);

        $method = self::isFunc() ? array_pop($names) : null;

        if ($global === 'GLOBALS') {
            if (empty($names)) {
                throw new SyntaxError('[023]Unexpected auto-global usage: '.implode('', self::$tokens));    
            }
            $script = '$GLOBALS';
            foreach ($names as $name) {
                $script .= '["'.$name.'"]';
            }

        } else {    // GET SERVER COOKIE SESSION
            if (count($names) !== 1) { 
                //@note when code is GET.keword.esc(), count($names) must be 1.
                throw new SyntaxError('[024]Unexpected auto-global usage: '.implode('', self::$tokens));
            }
            $script = '$_'.$global.'["'.$names[0].'"]';
        } 
        
        self::init();

        return $method
            ? self::wrapIfNeeded($script, $method)
            : $script;
    }
    
    private static function parseThis() {
        $names = self::$names;
        self::init();

        if (! self::isFunc() ) {
            if (count($names) === 1) {
                return '$this';
            }
            throw new SyntaxError('[025]access to object property is not allowd. 1'); // this.prop
        }
        if (count($names) !== 2) {
            throw new SyntaxError('[026]access to object property is not allowd. 2');  // this.prop.foo
        }
        return '$this->'.$names[1];     // this.method()
    }


    private static function parseLoopMember() {  
        $names = self::$names;
        $tokens = self::$tokens;
        $loopDepth = strlen(array_shift($tokens));

        if ($loopDepth > Statement::loopDepth()) {
            throw new SyntaxError('[027]depth of loop member "'.implode('', self::$tokens).'" is not correct.');
        }

        if (in_array($names[0], ['i', 's', 'k'])) {
            return self::_parseIsk($names, $loopDepth);
        }

        if ($names[0] === 'h') {
            return self::_parseH($names, $loopDepth);
        }

        return self::_parseV($names, $loopDepth);
    }
    private static function _parseIsk($names, $loopDepth) {
        if (count($names)===1) {
            if (self::isFunc()) {
                throw new SyntaxError('[028]Unexpected '.implode('', self::$tokens).'("');
            }
            self::init();
            return Statement::loopName($loopDepth, $names[0]);
        }
        if (count($names)===2 and self::isFunc()) {
            if (!self::isWrapper($names[1])) {
                //@note i,s and k cannot be object and so cannot have non-wrapper method.
                throw new FatalError("[029]".Scripter::$valWrapper." doesn't have method {$names[1]}()");
            }

            self::$expression->insertValWrapper();

            self::init();
            return Statement::loopName($loopDepth, $names[0]).')->'.$names[1];
        }
        //@note i,s and k cannot be array and so cannot have element.
        throw new SyntaxError('[030]Unexpected "'.implode('', self::$tokens).'"');
    }
    private static function _parseH($names, $loopDepth) {
        if (! (count($names) === 2 and self::isFunc())) {
            throw new SyntaxError('[033]Unexpected '.implode('', self::$tokens));
        }
        $helperMethod = strtolower($names[1]);
        if (!in_array($helperMethod, Scripter::loopHelperMethods())) {
            throw new FatalError('[031]loop helper method '.$helperMethod.'() is not defined.');
        }
        ['a'=>$a, 'i'=>$i, 's'=>$s, 'k'=>$k, 'v'=>$v] = Statement::loopNames($loopDepth);
        
        self::init();
        return Scripter::$loopHelper.'::_o('.$i.','.$s.','.$k.','.$v.')->'.$names[1];
    }
    private static function _parseV($names, $loopDepth) {
        if ($names[0]==='v') {
            $names = array_slice($names, 1);
        }

        $script = Statement::loopName($loopDepth, 'v');
        $method = self::isFunc() ? array_pop($names) : null;

        foreach ($names as $name) {
            $script .= '["'. $name.'"]';
        }

        self::init();
        return $method
            ? self::wrapIfNeeded($script, $method)
            : $script;
    }


    private static function parseStatic() {
        if (self::isFunc()) {
            if (count(self::names)===1) {
                return self::_parseFunc();
            }
            
            if ($script = self::_parseConstWithWrapperFunc()) {
                return $script;
            }

            $names = self::names;
            $func = array_pop($names);
            $path = '';
            foreach ($names as $name) {
                $path .= '\\'.$name;
            }
            if (class_exists($path)) {
                return self::_parseStaticMethod($path, $func);
            }
            return self::_parseNsFuncOrDynamicWithWrapper($names, $path, $func);
        }
        return self::_parseConst(self::names);
    }
 
    private static function _parseFunc() {
        $func = '\\'.self::names[0];
        if (!function_exists($func) and !in_array($func,['isset','empty'])) {
            throw new SyntaxError("[034]function `{$func}()` is not defined.");
        }
        self::init();
        return $func;
    }

    private static function _parseConstWithWrapperFunc() {
        $names = self::names;
        $func = array_pop($names);
        $script = self::_parseConst($names);

        if (!$script) {
            return false;
        }

        return self::wrapIfNeeded($script, $func, true);
    }

    private static function _parseStaticMethod($class, $method) {
        $script = $class.'::'.$method;
        if (!method_exists($class, $method)) {
            throw new FatalError("[035]Static method `{$script}()` not found.");
        }
        self::init();
        return $script;
    }
  
    private static function _parseNsFuncOrDynamicWithWrapper($names, $path, $func) {
        $script = $path.$func;
        if (function_exists($script)) {
            return $script;
        }

        return self::parseArray($names, $func);
    }

    private static function parseArray($names, $func=null) {
        $script = '$V';
        foreach ($names as $name) {
            $script .= '["'.$name.'"]';
        }

        self::init();
        return $func
            ? self::wrapIfNeeded($script, $func, true)
            : $script;
    }


    private static function wrapIfNeeded($script, $method, $onlyWrapper = false) {
        if (self::isWrapper($method)) {
            self::$expression->insertValWrapper();
            return $script . ')->' . $method;
        }
        if ($onlyWrapper) {
            throw new FatalError("[023]Wrapper method `{$method}` not found in `".implode('', self::$tokens)."`");    
        }
        return $script . '->' . $method;
    }

    private static function _parseConst($names) {
        $ns = '\\';
        $constName = null;
        foreach ($names as $name) {
            if (self::_constName($name)) {
                $constName = $name;
                break;
            } else {
                $ns = $name.'\\';
            }
        }
        
        if (!$constName) {
            return false;
        }

        $script = $ns.$constName;
        foreach ($names as $name) {
            if (self::_constName($name)) {
                throw new SyntaxError('[037]Unexpected constant usage: '.implode('', self::$tokens));    
            }
            $script .= "[{$name}]";
        }
        if (!defined($script)) {
            throw new FatalError("[038]Constant `{$script}` not found.");
        }

        return $script;
    }



/*    private static function _parseNsFuncOrStaticMethod() {
        $names = self::names;
        $func = array_pop($names);
        $path = '';
        foreach ($names as $name) {
            $path .= '\\'.$name;
        }
        if (class_exists($path)) {
            return self::_parseStaticMethod($path, $func);
        }
        return self::_parseNsFuncOrDynamicWithWrapper($path, $func);
    }*/
    
    
    /*private static function checkFuncName($name) {
        if (self::isConstantName($func)) { //@
            throw new SyntaxError("[032]function {$func}() has constant name.");
        }
    }*/

    private static function parseFunction___() {
        // 1. function
        if (count(self::$names) === 1) {
            $func = self::$names[0];
            self::checkFuncName($func);
            if (!function_exists($func) and !in_array($func,['isset','empty'])) {
                throw new SyntaxError("[]function {$func}() is not defined.");
            }
            self::init();
            return $func;
        }

        // 2
        $names = self::names;
        $method = array_pop($names);
        self::checkFuncName($method);
        $script = self::parseVariable($names);
        return self::wrapIfNeeded($script ,$func);

     

        $frontNames = [];
        $backNames  = self::$names;
        while ($name = array_shift($backNames)) {
            $frontNames[] = $name;
            if (self::isConstantName($name)) {
                $method = self::isFunc() ? array_pop($backNames) : null;
                $constExpr = self::parseConstantChain($frontNames, $backNames);
                self::init();
                return $method
                    ? self::wrapIfNeeded($constExpr, $method)
                    : $constExpr;
            }
        }
        // 3. function in namespace  or  method  or  wrapper method
        /*$names = self::$names;
        foreach($names as $name) {      // @ 상수에도 래퍼를 붙일 수 있다. 정정 필요
            if (self::isConstantName($name)) {
                throw new SyntaxError(implode('', self::$tokens).'() has constant name.');
            }
        }*/

        //$func  = array_pop($names);
        $name  = array_pop($names);
        $namespaces = empty($names) ? '' : '\\'.implode('\\', $names);

        // 3.1. namespace\functions
        $namespace = $name;
        $fullFunction = $namespaces.'\\'.$namespace.'\\'.$func;
        if (function_exists($fullFunction)) {
            self::init();
            return $fullFunction;
        }

        // 3.2. namespace\class::method
        if (self::isClassName($name)) {
            $class = $name;
            $fullClass = $namespaces.'\\'.$class;
            if (!class_exists($fullClass)) {
                throw new FatalError($fullClass.' does not exist');
            }
            if (!method_exists($fullClass, $func)) {
                throw new FatalError('static method '.$func.'() does not exist in '.$fullClass);
            }
            self::init();
            return $fullClass.'::'.$func;
        } 

        // 4. dynamic method
        $script = '$V';
        $names = self::$names;
        $method = array_pop($names);
        
        while ($name = array_shift($names)) {
            $script .= '["'.$name.'"]';
        }

        self::init();
        // 4.1. wrapper method
        if (in_array($method, Scripter::valWrapperMethods())) {
            self::$expression->insertValWrapper();
            return $script.')->'.$method;
        }
        // 4.2. object method
        return $script.'->'.$method;
    }

    private static function parseVariable($names) {
        $backnames = $names;
        $frontNames = [];
        while ($name = array_shift($$backnames)) {
            $frontNames[] = $name;
            if (self::isConstantName($name)) {
                self::init();
                return self::parseConstantChain($frontNames, $backnames);
            }
        }

        //$names = self::$names;
        $var = '$V';
        foreach ($names as $name) {
            $var .= '["'.$name.'"]';
        }
        self::init();
        return $var;
    }

    private static function parseConstantChain($frontNames, $backNames) {
        $constant = array_pop($frontNames);
        $path = '';
        foreach($frontNames as $name) {
            $path .= '\\'.$name;
        }
        if (defined($path.'\\'.$constant)) {
            return self::constantChain($path.'\\'.$constant, $backNames);
        } 
        if ($path and defined($path.'::'.$constant)) {
            return self::constantChain($path.'::'.$constant, $backNames);
        }
        throw new FatalError(
            empty($path)
            ? 'constant "'.$constant.'" is not defined.'
            : 'Neither '.$path.'\\'.$constant.' nor '.$path.'::'.$constant.' is defined.'
        );
    }
    private static function constantChain($constant, $backNames) {
        $constantChain = $constant;
        foreach($backNames as $name) {
            if (self::isConstantName($name)) {
                $constantChain .= '['.$name.']';
            } else {
                $constantChain .= '["'.$name.'"]';
            }
        }
        return $constantChain;
    }
    private static function isConstantName($name) {
        return preg_match('/^[A-Z][A-Z0-9_]*$/', $name);
        //return preg_match('/\p{Lu}/u', $name) and !preg_match('/\p{Ll}/u', $name);
    }

    private static function isClassName($name) {
        return preg_match('/^[A-Z]/', $name) and preg_match('/[a-z]/u', $name);
        //return preg_match('/^\p{Lu}/u', $name) and preg_match('/\p{Ll}/u', $name);
    }
}

/*

\func()
\Class::staticMethod()
\NS\func()
\NS\Class::staticMethod()

\CONST
\ARR_CONST['key']
\NS\CONST
\NS\ARR_CONST['key']
\Class::CONST
\Class::ARR_CONST['key']
\NS\Class::CONST
\NS\Class::ARR_CONST['key']

variable
arr.variable

NS 반복가능
ARR_CONST['key'] 에서 ['key'] 반복가능
arr.variable arr. 반복가능..

그리고 모든 경우에 래퍼붙을 수 있음..



foo().bar() 여기서 bar() 는 다음 번 NameDotChain 호출시에 처리되고..

\CONST['a']['b']         <--       [=CONST.a.b]
\Class::CONST['x']['y']  <--       [=Class.CONST.a.b]  특이사항 없어보이고


arr.variable[blah blah].other   ...... 여기서 other 는 지원하지 않아요...  .other() 래퍼만 붙일 수 있습니다.

foo().bar  -------- 이것도 지원하지 않아요.. bar() 래퍼만 붙일 수 있습니다. 지원하는게 좋을까요...


그리고 보니까.. parseFunction() / parseVariable() 로 나눴었는데
뭔가 느낌적인 느낌이..  parseStatic() / parseDynmic() 이렇게 구분하는게 쓸모가 있어보여요..

*/