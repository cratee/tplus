<?php

class Tplus {
    
    const SCRIPT_SIZE_PAD = 9;
    const VERSION = '1.2.0';

    private $config;
    private $data=[];
    private $phpReport;
    private $scripted = false;
    private static $renderDepth=0;
    private static $isShutdownRegistered = false;

    public function __construct($config) {
        $this->config = $config;
    }


    public function assign($keyOrArray, $val=null) {
        if (is_array($keyOrArray)) {
            $this->data = array_merge($this->data, $keyOrArray);

        } else if (is_string($keyOrArray)) {
            $this->data[$keyOrArray] = $val;

        } else {
            trigger_error("assign() expects array or string.", E_USER_ERROR);
        }

        return '';
    }

    public function get($path) {
        if (!$scriptPath = $this->getScriptPath($path)) {
            return '';
        }

        $V = &$this->data;

        $ob_level = ob_get_level();
        ob_start();
        
        $start_tpl = (self::$renderDepth === 0);
        self::$renderDepth++;

        if ($start_tpl) {
            $this->modifyErrorReporting();
            $this->setErrorHandler();         
        }

        try {
            $render_result = '';
            require $scriptPath;
            $render_result = ob_get_clean();

        } finally {
            if (ob_get_level() > $ob_level) {
                @ob_end_flush();
            }

            if ($start_tpl) {
                $this->unsetErrorHandler();
                $this->restoreErrorReporting();
            }

            self::$renderDepth--;
        }

        return $render_result;
    }

    /**
     * @deprecated since v1.1.3 this method is kept for backward compatibility.
     *             Use get($path) instead.
     *
     * fetch() is alias of get() for loading sub-templates.
     */
    public function fetch($path) {
        return $this->get($path);
    }
    public function isScripted() {
        return $this->scripted;
    }
    private function getScriptPath($path) {
        $htmlPath   = $this->config['HtmlRoot'].$path;
        $scriptPath = $this->config['HtmlScriptRoot'].$path.'.php';

        if ($this->config['ScriptCheck']) {
            if (!is_file($htmlPath)) {
                trigger_error(
                    "Tpl config `'ScriptCheck' => true` but Tplus cannot find HTML file: `{$htmlPath}`",
                    E_USER_ERROR
                );
                return false;
            }
            if ($this->needsScripting($htmlPath, $scriptPath)) {
                $this->script($htmlPath, $scriptPath);
            }

        } else if (!is_file($scriptPath)) {
            trigger_error(
                "Tpl config `'ScriptCheck' => false` but Tplus cannot find Script file: `{$scriptPath}`",
                E_USER_ERROR
            );
            return false;
        }

        return $scriptPath;
    }
    
    private function script($htmlPath, $scriptPath) {
        require_once __DIR__.'/TplusScripter.php';
        \Tplus\Scripter::script(
            $htmlPath, 
            $scriptPath, 
            self::SCRIPT_SIZE_PAD, 
            $this->scriptHeader($htmlPath), 
            $this->config
        );

        $this->scripted = true;
    }

    private function needsScripting($htmlPath, $scriptPath) {
        if (!is_file($scriptPath)) {
            return true;
        }

		$headerExpected = $this->scriptHeader($htmlPath);
        $headerWritten = file_get_contents(
            $scriptPath, false, null, 0, 
            strlen($headerExpected) + self::SCRIPT_SIZE_PAD
        );

        return !(
            strlen($headerWritten) > self::SCRIPT_SIZE_PAD
            and $headerExpected == substr($headerWritten, 0, -self::SCRIPT_SIZE_PAD)
            and filesize($scriptPath) == (int)substr($headerWritten, -self::SCRIPT_SIZE_PAD) 
        );
    }

    private function scriptHeader($htmlPath) {
		$fileMTime = @date('Y-m-d H:i:s', filemtime($htmlPath));
		return '<?php /* Tplus '.self::VERSION.' '.$fileMTime.' '.realpath($htmlPath).' ';
    }

    private function setErrorHandler() {
        set_error_handler(function($type, $message, $file, $line) {
            if (error_reporting() & $type) {
                require_once __DIR__.'/TplusError.php';
                \TplusError::handle(['type'=>$type, 'message'=>$message, 'file'=>$file, 'line'=>$line]);
            }
        });

        if (!self::$isShutdownRegistered) {
            register_shutdown_function(function() {            
                require_once __DIR__.'/TplusError.php';
                \TplusError::handle(error_get_last());
            });
            self::$isShutdownRegistered = true;
        }
    }
    private function unsetErrorHandler() {
        restore_error_handler();
    }

    private function modifyErrorReporting() {
        if (!$this->_changeErrorReporting()) {
            return;
        }
        $this->phpReport = error_reporting();
        $AssignErrorBit = $this->_getAssignErrorBit();
        error_reporting($this->phpReport & ~$AssignErrorBit);
    }
    private function restoreErrorReporting() {
        if (!$this->_changeErrorReporting()) {
            return;
        }
        error_reporting($this->phpReport);
    }
    private function _getAssignErrorBit() {
        return version_compare(phpversion(), '8.0.0', '<') ? E_NOTICE : E_WARNING;
    }
    private function _changeErrorReporting() {
        return isset($this->config['AssignCheck']) and $this->config['AssignCheck']==false;
    }

    /**
     * run Data Chain
     */
    private static function runChain($var, $chain) {
        if (empty($chain)) {
            return $var;
        }
        end($chain);
        $lastIndex = key($chain);
        foreach ($chain as $i => $name) {
            if (is_array($var)) {
                if (!array_key_exists($name, $var)) {
                    trigger_error("Tplus Runtime: Index `{$name}` not found in array.");
                    return null;
                }
                $var = $var[$name];
            } else if (is_object($var)) {
                try {
                    $var = $var->$name;
                } catch (\Throwable $e) {
                    trigger_error("Tplus Runtime: Property `{$name}` not found in object of class " . get_class($var));
                    return null;
                }
            } else if (is_null($var)) {
                trigger_error("Tplus Runtime: Cannot access key `{$name}` on a null value.");
                return null;
            } else {
                if ($i !== $lastIndex) {
                    trigger_error("Tplus Runtime: Cannot access key `{$name}` on `{$var}`");
                    return null;
                }
            }
        }
        return $var;
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