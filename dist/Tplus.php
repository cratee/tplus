<?php

class Tplus {
    
    const SCRIPT_SIZE_PAD = 9;
    const VERSION = '1.1.3';

    private $config;
    private $data=[];
    private $phpReport;
    private $renderDepth=0;

    public function __construct($config) {
        $this->config = $config;
    }

    public function assign($data) {
        $this->data = array_merge($this->data, $data);
        return '';
    }

    public function get($path) {
        $scriptPath = $this->config['HtmlScriptRoot'].$path.'.php';
        
        if ($this->config['ScriptCheck']) {
            $this->checkScript($path, $scriptPath);

        } else if (!is_file($scriptPath)) {
            trigger_error(
                "Tpl config `'ScriptCheck' => false` but Tplus cannot find Script file: `{$scriptPath}`",
                E_USER_ERROR
            );
        }

        $V = &$this->data;

        $ob_level = ob_get_level();
        ob_start();
        
        $start_tpl = ($this->renderDepth === 0);
        $this->renderDepth++;

        if ($start_tpl) {
            $this->stopAssignCheck();
            $this->setErrorHandler();         
        }

        try {
            $render_result = '';
            require $scriptPath;
            $render_result = ob_get_clean();

        } finally {
            if (ob_get_level() > $ob_level) {
                @ob_end_clean();
            }

            if ($start_tpl) {
                $this->unsetErrorHandler();
                $this->startAssignCheck();
            }

            $this->renderDepth--;
        }

        return $render_result;
    }

    /**
     * @deprecated since v1.1.3 This method is kept for backward compatibility.
     *             Use get($path) instead.
     *
     * fetch() is alias of get() for loading sub-templates.
     */
    public function fetch($path) {
        return $this->get($path);
    }

    private function script($htmlPath, $scriptPath) {
        include_once dirname(__file__).'/TplusScripter.php';
        \Tplus\Scripter::script(
            $htmlPath, 
            $scriptPath, 
            self::SCRIPT_SIZE_PAD, 
            $this->scriptHeader($htmlPath), 
            $this->config
        );
    }

    private function checkScript($path, $scriptPath) {
        $htmlPath = $this->config['HtmlRoot'].$path;

        if (!$this->isScriptValid($htmlPath, $scriptPath)) {
            $this->script($htmlPath, $scriptPath);
        }
    }
    
    private function isScriptValid($htmlPath, $scriptPath) {        
		if (!is_file($htmlPath)) {
			trigger_error(
                "Tpl config `'ScriptCheck' => true` but Tplus cannot find HTML file: `{$htmlPath}`",
                E_USER_ERROR
            );
		}
        if (!is_file($scriptPath)) {
            return false;
        }

        return $this->isScriptUpdated($htmlPath, $scriptPath);
    }

    private function isScriptUpdated($htmlPath, $scriptPath) {
		$headerExpected = $this->scriptHeader($htmlPath);
        $headerWritten = file_get_contents(
            $scriptPath, false, null, 0, 
            strlen($headerExpected) + self::SCRIPT_SIZE_PAD
        );

        return (
            strlen($headerWritten) > self::SCRIPT_SIZE_PAD
            and $headerExpected == substr($headerWritten, 0, -self::SCRIPT_SIZE_PAD)
            and filesize($scriptPath) == (int)substr($headerWritten,-self::SCRIPT_SIZE_PAD) 
        );
    }
    private function scriptHeader($htmlPath) {
		$fileMTime = @date('Y-m-d H:i:s', filemtime($htmlPath));
		return '<?php /* Tplus '.self::VERSION.' '.$fileMTime.' '.realpath($htmlPath).' ';
    }

    private function setErrorHandler() {
        set_error_handler(function($type, $message, $file, $line) {
            if (error_reporting() & $type) {
                include_once dirname(__file__).'/TplusError.php';
                \TplusError::handle(['type'=>$type, 'message'=>$message, 'file'=>$file, 'line'=>$line]);
            }
        });

        register_shutdown_function(function() {            
            include_once dirname(__file__).'/TplusError.php';
            \TplusError::handle(error_get_last());
        });
    }
    private function unsetErrorHandler() {
        restore_error_handler();
    }

    private function stopAssignCheck() {
        if ($this->_checkAssign()) {
            return;
        }
        $this->phpReport = error_reporting();
        $AssignErrorBit = $this->_getAssignErrorBit();
        error_reporting($this->phpReport & ~$AssignErrorBit);
    }
    private function startAssignCheck() {
        if ($this->_checkAssign()) {
            return;
        }
        error_reporting($this->phpReport);
    }
    private function _getAssignErrorBit() {
        return version_compare(phpversion(), '8.0.0', '<') ? E_NOTICE : E_WARNING;
    }
    private function _checkAssign() {
        return !isset($this->config['AssignCheck']) or $this->config['AssignCheck']==true;
    }

}


class TplusWrapper {
    
    static protected $instance;

    final public static function o($x) {
        if (is_object($x)) {
            return $x;
        }
        if (empty(static::$instance)) {
            static::$instance = new static;
        }
        static::$instance->x = $x;
        return static::$instance;
    }

    public function esc() {
        return htmlspecialchars($this->x);
    }

    public function nl2br() {
        return nl2br($this->x);
    }

    public function toUpper() {
        return strtoupper($this->x);
    }

    public function toLower() {
        return strtolower($this->x);
    }

    public function ucfirst() {
        return ucfirst($this->x);
    }

    public function substr($a, $b=null) {
        return is_null($b) ? substr($this->x, $a) : substr($this->x, $a, $b);
    }

    public function concat() {
        return $this->x . implode('',func_get_args());
    }
}


class TplusLoopHelper {

    static protected $instance;

    final public static function o($i, $s, $k, $v) {
        if (empty(static::$instance)) {
            static::$instance = new static;
        }
        static::$instance->i = $i;
        static::$instance->s = $s;
        static::$instance->k = $k;
        static::$instance->v = $v;
        return static::$instance;
    }
}