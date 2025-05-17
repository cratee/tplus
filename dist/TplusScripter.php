<?php
/**
    ------------------------------------------------------------------------------
    Tplus 1.0.5
    Released 2025-05-01

    
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

/**
 * The Tplus Scripter is a lightweight transformer that compiles HTML template files into PHP scripts.
 */
class Scripter {

    public static $currentLine = 1;
    public static $valWrapper;
    public static $loopHelper;
    public static $userCode;

    public static function script($htmlPath, $scriptPath, $sizePad, $header, $config) {
    
        self::$valWrapper = '\\'.(empty($config['ValWrapper']) ? 'TplValWrapper' : $config['ValWrapper']);
        self::$loopHelper = '\\'.(empty($config['LoopHelper']) ? 'TplLoopHelper' : $config['LoopHelper']);
        try {
            self::$userCode = self::getHtml($htmlPath);        
            self::saveScript($config['HtmlScriptRoot'], $scriptPath, $sizePad, $header, self::parse()); 

        } catch(SyntaxError $e) {
            self::reportError('Tplus Scripter Syntax Error ', $e->getMessage(), $htmlPath, self::$currentLine);

        } catch(FatalError $e) {
            self::reportError('Tplus Scripter Fatal Error ', $e->getMessage(), $htmlPath, self::$currentLine);
        }
    }

    private static function reportError($title, $message, $htmlPath, $currentLine)
    {
        $htmlPath = realpath($htmlPath);
        if (Statement::$rawTag) {
            $message .= ' in `'.Statement::$rawTag.'`';
        }
        if (ini_get('log_errors')) {
            error_log($title. $message.' in '.$htmlPath.' on line '.$currentLine);
        }
        if (ini_get('display_errors')) {
            include_once dirname(__file__).'/TplusError.php';
            \TplusError::display($htmlPath, $currentLine, Statement::$rawTag, 0, $message, $title, false);
            if (ob_get_level()) ob_end_flush();
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
                    throw new FatalError('[036] fail to create directory '.$path.' check the write-permission.');
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
        if (!isset($methods[$class])) {
            $methods[$class] = [];
            if (!class_exists($class)) {
                throw new FatalError('[039]Class "'.substr($class, 1).'" does not exist.');
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

        if ($commandStack = Statement::commandStack()) {
            while ($command = $commandStack->pop()) {
                if (!$command or in_array($command, ['@', '?'])) {
                    break;
                }
            }
            if ($command) {
                throw new SyntaxError("[044] `[{$command}...]` is not closed with [/]");
            }
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
        $scriptTagPattern = ini_get('short_open_tag') ? '~(<\?)~' : '~(<\?(php\s|=))~i';
        // @note <% and <script language=php> removed since php 7.0

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
        return $this->isEmpty() ? null : end($this->items);
    }
	public function isEmpty() {
		return empty($this->items);
	}
	public function count($item = null) {
		return  $item 
            ? array_count_values($this->items)[$item]
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
    public static $rawTag;

    public static function commandStack() {
        return self::$commandStack;
    }

    public static function script($command) {
    
        if (!isset(self::$commandStack)) {
            self::$commandStack = new Stack;
        }

        self::$rawTag = "[$command";

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
                    throw new SyntaxError("Unexpected `:` command");
                }
                switch($prevCommand) {
                    case '?': $script = self::parseElse();      break;
                    case '@': $script = self::parseLoopElse();  break;
                }
        }

        self::parseRightTag();

        //self::$rawTag = '';
        return "<?php {$script} ".self::getComment()."?>\n";
    }

    private static function parseEcho() {
        $expression = Expression::script();
        self::parseRightTag();
        return "<?={$expression} ".self::getComment()."?>\n";
    }

    private static function getComment() {
        $meta = [
            'line' => Scripter::$currentLine,
            'code' => str_replace('*/', '*\/', Statement::$rawTag).']'
        ];
        self::$rawTag = '';
        return '/*'.json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).'*/';
    }

    private static function parseEnd() {
        $script = ('@' === self::$commandStack->peek()) ? '}}' : '}';
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

/**
 * The Cxt (Child Expression Trigger) class determines the context of a new expression.
 * 
 * It is responsible for:
 *  - validating the triggering token of a child expression,
 *  - consuming the Token::OPEN or Token::DELIMITER token,
 *  - and returning the appropriate Cxt::<CONST> representing the new expression context.
 */
class Cxt {
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

    public static function get($prevToken, $currToken, $cxt) {
        switch ($currToken['value']) {
            case '{': return $prevToken['name']=='Name' ? self::ICE : self::JCE;
            case '[': return $prevToken['name']=='Name' ? self::IKT : self::JKT;
            case '(': return $prevToken['name']=='Name' ? self::FUN : self::PAR;
            case '?': return self::ternary($prevToken, $currToken, $cxt);
            case ',': return self::comma($cxt);
            case ':': return self::colon($cxt);
        }
    }
    private static function comma($cxt) {
        switch ($cxt) {
            case self::JCE : return self::JCE_COMMA;
            case self::JKT : return self::JKT_COMMA;
            case self::FUN : return self::FUN_COMMA;
            case self::JCE_COMMA : return self::JCE_COMMA;
            case self::JKT_COMMA : return self::JKT_COMMA;
            case self::JCE_COLON : return self::JCE_COMMA;
            case self::JKT_COLON : return self::JKT_COMMA;
        }
        throw new SyntaxError('[018] Invalid token: `,`'); 
        // This excetion should never be reached. Token `,` already validated by CxStop::isValid().
    }
    private static function colon($cxt) {
        switch ($cxt) {
            case self::JCE : return self::JCE_COLON;
            case self::JKT : return self::JKT_COLON;
            case self::TRN : return self::TRN_COLON;
            case self::JCE_COMMA : return self::JCE_COLON;
            case self::JKT_COMMA : return self::JKT_COLON;
        }
        throw new SyntaxError('[017]Invalid token: `:`');
        // This excetion should never be reached. Token `:` already validated by CxStop::isValid().
    }
    private static function ternary($prevToken, $currToken, $cxt) {
        if ($prevToken['group'] & (Token::OPERAND | Token::CLOSE) 
            and !($cxt & (self::TRN | self::TRN_COLON))) {
            return self::TRN;
        }
        throw new SyntaxError('[016]Invalid token: `?`');
    }
}


/**
 * The CxStop (Child Expression Stop) class determines whether a child expression has ended.
 * 
 * Used by Expression->isFinished(), it maps each context (Cxt::<CONST>) to valid stopping tokens
 * such as closing brackets or delimiters.
 */
class CxStop {
    private static $map = [
    //  $parentCxt      => $stopCode
        Cxt::FUN        => ['end' => [')'],             'check' => [',']],
        Cxt::JKT        => ['end' => [']'],             'check' => [':', ',']],
        Cxt::JCE        => ['end' => ['}'],             'check' => [':', ',']],
        Cxt::PAR        => ['check' => [')']],
        Cxt::ICE        => ['check' => ['}']],
        Cxt::IKT        => ['check' => [']']],
        Cxt::TRN        => ['check' => [':']],
        Cxt::FUN_COMMA  => ['check' => [',', ')']],
        Cxt::JCE_COMMA  => ['check' => [',', ':', '}']],
        Cxt::JCE_COLON  => ['check' => [',', '}']],
        Cxt::JKT_COMMA  => ['check' => [',', ':', ']']],
        Cxt::JKT_COLON  => ['check' => [',', ']']],
        Cxt::TRN_COLON  => ['check' => [',', ')', ']', '}']],
    ];
    public static function isValid($parentCxt, $stopCode, $expession) {
        if (!$parentCxt and $stopCode === ']') { // end tag
            return true;
        }

        $map = self::$map[$parentCxt] ?? null;
        // @note after ternary expression is finished, `)` in [= a?b:c )] --> $parentCxt==0 --> $map == null  

        if (!empty($map['end']) and in_array($stopCode, $map['end'])) {
            return true;
        }

        if (!empty($map['check']) and in_array($stopCode, $map['check'])) {
            return $expession->preventEmptyExpression($stopCode);
        }
        
        Statement::$rawTag .= $stopCode;
        throw new SyntaxError("[014] Unexpected token `{$stopCode}`");
    }
}

class Expression {

    private $cxt = 0;
    private $scriptPieces = [];
    private $wrapperStartIndex = -1;
    private $wrapperTrigger = null;

    public static function script($parentCxt = 0) {
        
        $expression = new Expression();
        $expression->parse($parentCxt);
        return $expression->assembleScriptPieces();
    }
                    
    public function preventEmptyExpression($stopCode) {
        if (empty($this->scriptPieces)) {
            throw new SyntaxError("[013]Unexpected token `{$stopCode}`:  missing expression before `{$stopCode}`");
        }
        return true;
    }

    private function assembleScriptPieces() {
        return implode('', $this->scriptPieces);
    }

    private function parse($parentCxt) {
        $afterCx = false;
        $prevToken = ['group'=>0, 'name'=>'', 'value'=>''];
        NameDotChain::init();

        for (;;) {
            if ($this->isFinished($parentCxt, $afterCx)) {
                break;
            }

            $afterCx = false;
            $currToken = $this->nextToken();

            Statement::$rawTag .= $currToken['value'];

            if ($currToken['group'] === Token::SPACE) {
                continue;
            }

            $this->normalizeBI_UNARY($prevToken, $currToken);
            $this->checkTokensOrder($prevToken, $currToken);
            $this->setWrapperStartIndex($currToken);

            if (!in_array($currToken['name'], ['Name', 'Dot'])) {
                NameDotChain::init();
            }

            $this->scriptPieces[] = $this->{'parse'.$currToken['name']}($prevToken, $currToken);

            if ($currToken['group'] & Token::CXT) {

                $this->cxt = Cxt::get($prevToken, $currToken, $this->cxt);
                $this->scriptPieces[] = self::script($this->cxt);
                $afterCx = true;
            }

            $prevToken = $currToken;
        }
    }

    private function isFinished($parentCxt, $afterCx) {
        if (! Scripter::$userCode ) {
            throw new SyntaxError('[015]HTML file ends without Tplus closing tag `]`');
        }
        
        $stopCode = substr(Scripter::$userCode, 0, 1);

        if (!in_array($stopCode, [')', '}', ']', ':', ','])) {
            return false;
        }

        if ($afterCx) {
            // Ternary expressions (a ? b : c) require two-step termination:
            // 1st: when inner expression `c` finishes,
            // 2nd: expression `a ? :`  must be finished without consuming stopCode `)`
            return $this->cxt == Cxt::TRN_COLON 
                ? true 
                : false;
        }

        return CxStop::isValid($parentCxt, $stopCode, $this);
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
        throw new SyntaxError('[013] Invalid expression: '.substr(Scripter::$userCode,0,10).' ...');
    }

    private function normalizeBI_UNARY($prevToken, &$currToken) {
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
            throw new SyntaxError("[012] Unexpected `{$prevToken['value']}{$currToken['value']}`");
        }
        if ((!$prevToken['group'] || $prevToken['group'] & (Token::OPERATOR|Token::UNARY))
            and $currToken['group'] === Token::OPERATOR) {
            // @note OPEN|CLOSE|DELIMITER are checked in CxStop::isValid()
            throw new SyntaxError("[011] Unexpected `{$prevToken['value']}{$currToken['value']}`");
        }
        if ($prevToken['group'] === Token::UNARY and $currToken['group']===Token::UNARY) {
            throw new SyntaxError("[042] Consecutive Unary Operators are not allowed `{$prevToken['value']}{$currToken['value']}`");
        }
        if ($prevToken['group'] === Token::DOT and $currToken['name'] !== 'Name') {
            throw new SyntaxError("[010] Unexpected `{$prevToken['value']}{$currToken['value']}`");
        }
        if (($prevToken['group'] 
             && !($prevToken['group'] & (Token::OPERATOR|Token::UNARY)) 
             && $prevToken['name'] !== 'Name')
            and $currToken['group']===Token::OPEN) {
            throw new SyntaxError("[043] Unexpected `{$prevToken['value']}{$currToken['value']}`");
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
        return '(';
    }
    private function parseBraceOpen($prevToken, $currToken) {
        return '[';
    }
    private function parseBracketOpen($prevToken, $currToken) {        
        return '[';
    }
    private function parseTernaryIf($prevToken, $currToken) {        
        return '?';
    }

    // @note stopCodes )}]:, have already been checked in CxStop::isValid()
    private function parseParenthesisClose($prevToken, $currToken) {
        return ')';
    }
    private function parseBraceClose($prevToken, $currToken) {
        return ']';
    }
    private function parseBracketClose($prevToken, $currToken) {
        return ']';
    }
    private function parseComma($prevToken, $currToken) {
        return ',';
    }
    private function parseTernaryElseOrKVDelim($prevToken, $currToken) {        
        if ($this->cxt === Cxt::TRN) {
            return ':';
        }
        if ($this->cxt & (Cxt::JCE | Cxt::JKT | Cxt::JCE_COMMA | Cxt::JKT_COMMA )) {
            return '=>';
        }
    }

    private function parseNumber($prevToken, $currToken) {
        return $currToken['value'];
    }

    private function parseQuoted($prevToken, $currToken) {
        if ($prevToken['group']===Token::UNARY) {
            //@policy  String literal cannot follows unary operators.            
            throw new SyntaxError('[001]Unexpected '.$currToken['value']);
        }
        if ($prevToken['name'] == 'Plus') {
            //@policy  Plus operator before string literal is changed to concat operator.
            array_pop( $this->scriptPieces );
            array_push($this->scriptPieces, '.');
        }
        return $currToken['value'];
    }
    private function parsePlus($prevToken, $currToken) {
        return ($prevToken['name'] == 'Quoted') ? '.' : ' +';
    }
    private function parseMinus($prevToken, $currToken) {
        return $currToken['value'];
    }

    
    private function parseUnary($prevToken, $currToken) {
        return $currToken['value'];
    }
    private function parseXcrement($prevToken, $currToken) {
        throw new SyntaxError('[020]Increment ++ or decrement -- operators are not allowd.');
    }
    private function parseComparison($prevToken, $currToken) {
        return $currToken['value'];  // === == !== != < > <= >=     @todo check a == b == c
    }
    private function parseLogic($prevToken, $currToken) {
        return $currToken['value'];  // && ||
    }
    private function parseElvis($prevToken, $currToken) {
        return $currToken['value'];  // ?: ??
    }
    private function parseArithOrBit($prevToken, $currToken) {
        return $currToken['value'];  // % * / & ^ << >>             @todo check if operand is string
    }

    private function parseDot($prevToken, $currToken) {

        if ($prevToken['group']===Token::CLOSE
            or $prevToken['group']===Token::OPERAND && $prevToken['name']!=='Name') {

            $this->wrapperTrigger = $prevToken['name'];
            return;
        }

        NameDotChain::addDot($currToken['value']);
    }
    private function parseName($prevToken, $currToken) { // variable, function, object, method, array, key, namespace, class

        if ($this->wrapperTrigger) {
            NameDotChain::confirmWrapper($currToken['value'], $this->wrapperTrigger=='Name');
            $this->insertValWrapper();
            $this->wrapperTrigger = null;
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
        self::$expression = null;
    }

    public static function confirmWrapper($name, $onlyWrapper) {
        if (!self::isFunc()) {
            throw new SyntaxError("[007]unexpected {$name}");
        }
        if ($onlyWrapper and !self::isWrapper($name)) {
            throw new SyntaxError("[041]Wrapper method {$name}() not found in class ".Scripter::$valWrapper);
        }
    }

    public static function addDot($token) {
        if (!self::isEmpty() and strlen($token) > 1) { //  multiple dots after Name. e.g) 'abc..' 'xyz...'
            throw new SyntaxError('[008]Unexpected consecutive dots in expression: '.implode('', self::$tokens).$token);
        }
        self::$tokens[] = $token;
    }
    public static function addName($token, $expression) {

        self::$expression = $expression;
        if (in_array($token, ['true', 'false', 'null', 'this'])) {
            if (!self::isEmpty() or self::isFunc()) {
                throw new SyntaxError("[021]`{$token}` is reserved word.");
            }
            if ($token !== 'this') { 
                if (self::isNextDot()) {
                    throw new SyntaxError("[022]`{$token}` is reserved word.");
                }
                return $token;
            }
        }

        self::$tokens[] = $token;
        self::$names[] = $token;
        
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
                // when code is GET.keword.esc(), count($names) must be 1.
                throw new SyntaxError('[024]Unexpected auto-global usage: '.implode('', self::$tokens));
            }
            $script = '$_'.$global.'["'.$names[0].'"]';
        } 
        
        return $method
            ? self::wrapIfNeeded($script, $method, true)
            : $script;
    }
    
    private static function parseThis() {
        $names = self::$names;

        if (! self::isFunc() ) {
            if (count($names) === 1) {
                return '$this';
            }
            throw new SyntaxError('[025]access to object property is not allowd.'); // this.prop
        }
        $method = array_pop($names);
        if (count($names) !== 1) {
            throw new SyntaxError('[026]access to object property is not allowd.');  // this.prop.foo()
        }
        return '$this->'.$method;     // this.method()
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
                throw new SyntaxError('[028] Unexpected '.implode('', self::$tokens).'("');
            }
            return Statement::loopName($loopDepth, $names[0]);
        }
        if (count($names)===2 and self::isFunc()) {
            if (!self::isWrapper($names[1])) {
                //@note i,s and k cannot be object and so cannot have non-wrapper method.
                throw new FatalError("[029] ".Scripter::$valWrapper." doesn't have method {$names[1]}()");
            }

            self::$expression->insertValWrapper();

            return Statement::loopName($loopDepth, $names[0]).')->'.$names[1];
        }
        // @note i,s and k cannot be array and so cannot have element.
        throw new SyntaxError('[030] Unexpected "'.implode('', self::$tokens).'"');
    }
    private static function _parseH($names, $loopDepth) {
        if (! (count($names) === 2 and self::isFunc())) {
            throw new SyntaxError('[033] Unexpected '.implode('', self::$tokens));
        }
        $helperMethod = strtolower($names[1]);
        if (!in_array($helperMethod, Scripter::loopHelperMethods())) {
            throw new FatalError('[031] loop helper method '.$helperMethod.'() is not defined.');
        }
        ['a'=>$a, 'i'=>$i, 's'=>$s, 'k'=>$k, 'v'=>$v] = Statement::loopNames($loopDepth);
        
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

        return $method
            ? self::wrapIfNeeded($script, $method)
            : $script;
    }


    private static function parseStatic() {
        if (self::isFunc()) {
            if (count(self::$names)===1) {
                return self::_parseFunc();
            }
            
            if ($script = self::_parseConstWithWrapperFunc()) {
                return $script;
            }

            $names = self::$names;
            $func = array_pop($names);
            $path = '';
            foreach ($names as $name) {
                $path .= '\\'.$name;
            }
            if (class_exists($path)) {
                return self::_parseStaticMethod($path, $func);
            }
            return self::_parseNsFuncOrArrayWithWrapper($names, $path, $func);
        }
        return self::_parseConst(self::$names);
    }
 
    private static function _parseFunc() {
        $func = self::$names[0];
        if (!function_exists($func) and !in_array($func,['isset','empty'])) {
            throw new SyntaxError("[034] function `{$func}()` is not defined.");
        }
        return $func;
    }

    private static function _parseConstWithWrapperFunc() {
        $names = self::$names;
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
            throw new FatalError("[035] Static method `{$script}()` not found.");
        }
        return $script;
    }
  
    private static function _parseNsFuncOrArrayWithWrapper($names, $path, $func) {
        $script = $path.'\\'.$func;
        
        if (function_exists($script)) {
            return $script;
        }

        return self::parseArray($names, $func);
    }

    private static function parseArray($names, $func=null) {
        $script = '$V';
        foreach ($names as $name) {
            $script .= "['{$name}']";
        }
        return $func
            ? self::wrapIfNeeded($script, $func)
            : $script;
    }


    private static function wrapIfNeeded($script, $method, $onlyWrapper = false) {
        if (self::isWrapper($method)) {
            self::$expression->insertValWrapper();
            return $script . ')->' . $method;
        }
        if ($onlyWrapper) {
            throw new FatalError("[023] Wrapper method `".Scripter::$valWrapper."::{$method}()` not found in `".implode('', self::$tokens)."`");    
        }
        return $script . '->' . $method;
    }

    private static function _parseConst($names) {
        $path = '';
        $constName = null;
        while ($name = array_shift($names)) {
            if (self::_constName($name)) {
                $constName = $name;
                break;
            } else {
                $path .= '\\'.$name;
            }
        }
        
        if (!$constName) {
            return false;
        }

        if ($path and class_exists($path)) {
            $script = $path ? $path.'::'.$constName : '\\'.$constName ;
            if (!defined($script)) {
                throw new FatalError("[040] Constant `{$script}` not found.");    
            }
        } else {
            $script = $path.'\\'.$constName;
            if (!defined($script)) {
                throw new FatalError("[038] Constant `{$script}` not found.");
            }
        }

        foreach ($names as $name) {
            if (self::_constName($name)) {
                throw new SyntaxError('[037] Unexpected constant usage: '.implode('', self::$tokens));    
            }
            $script .= "['{$name}']";
        }

        return $script;
    }
    private static function _constName($name) {
        return preg_match('/^[A-Z][A-Z0-9_]*$/', $name);
    }
}

