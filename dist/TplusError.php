<?php
namespace Tplus;

class FileNotFound extends \Error {}

class Error {

    public static function handleFileNotFound($e) {
        
        $file = '';
        $line = '';
        $code = '';

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        foreach ($trace as $step) {
            if (!empty($step['file']) and
                strpos(basename($step['file']), 'Tpl') === false) {
                $file = $step['file'];
                $line = $step['line'];
                break;
            }
        }

        if (is_file($file)) {
            $file_obj = new \SplFileObject($file);
            if ($line > 0) {
                $file_obj->seek($line - 1);
                if ($file_obj->valid()) {
                    $code = trim($file_obj->current());
                }
            }
        }
        self::report([
            'title'  =>'Tplus Runtime',
            'type'   => '\\'.get_class($e),
            'message'=> $e->getMessage(),
            'escape' => false,
            'file'   => $file,
            'line'   => $line,
            'code'   => $code
        ]);
    }

    public static function handleThrowable($e) {

        if (!$e instanceof \Throwable) return;
    
        [$file, $line, $code] = self::getTracedHtmlInfo($e->getFile(), $e->getLine(), $e->getTrace());

        if (!$file) {
            $file = $e->getFile();
            $line = $e->getLine();
        }
        
        self::report([
            'title'  =>'Tplus Runtime',
            'type'   => '\\'.get_class($e),
            'message'=> $e->getMessage(),
            'escape' => true,
            'file'   => $file,
            'line'   => $line,
            'code'   => $code
        ]);
    }

    public static function handleLegacy($e) {

        if (!is_array($e)) return;

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        if (E_USER_WARNING == $e['type']) {
            $type = (PHP_VERSION_ID < 80000) ? 'E_NOTICE' : 'E_WARNING';
        } else {
            $type = [
                E_NOTICE    =>'E_NOTICE',
                E_WARNING   =>'E_WARNING',
                E_DEPRECATED=>'E_DEPRECATED',
                E_STRICT    =>'E_STRICT'
            ][$e['type']] ?? '';
        }

        [$file, $line, $code] = self::getTracedHtmlInfo($e['file'], $e['line'], $trace);

        if (!$file) {
            $file = $e['file'];
            $line = $e['line'];
        }

        self::report([
            'title'  =>'Tplus Runtime',
            'type'   => $type,
            'message'=> $e['message'],
            'escape' => true,
            'file'   => $file,
            'line'   => $line,
            'code'   => $code
        ]);
    }

    private static function getTracedHtmlInfo($file, $line, $trace) {
        array_unshift($trace, ['file' => $file, 'line' => $line]);

        foreach ($trace as $step) {
            if (empty($step['file']) || empty($step['line'])) {
                continue;
            }

            try {
                $fileObj = new \SplFileObject($step['file'], 'r');
            } catch (\RuntimeException $e) {
                continue;
            }

            $fileObj->seek(0);
            $firstLine = $fileObj->current();

            if (!$firstLine || !preg_match('~Tplus(?:\s\S+){3}\s(.+?)\s\d+\s\*/~', $firstLine, $m)) {
                continue;
            }
            $htmlFile = $m[1];

            $targetLineIndex = $step['line'] - 1;
            $fileObj->seek($targetLineIndex);
            
            if ($fileObj->key() === $targetLineIndex) {
                $lineContent = $fileObj->current();
                if (preg_match('~/\*\s*(\{.+?\})\s*\*/\s*\?>~', $lineContent, $m)) {
                    $json = json_decode($m[1], true);
                    if ($json) {
                        return [$htmlFile, $json['line'], $json['code']];
                    }
                }
            }
        }

        return [null, null, null];
    }

    private static $hashes = [];

    private static function report($data) {
        $hash = md5(json_encode($data));
        if (isset(self::$hashes[$hash])) return;
        self::$hashes[$hash] = true;

        extract($data);

        if (ini_get('log_errors')) {
            error_log("[Tplus] {$message} in {$file} on line {$line}: {$code}");
        }
        if (ini_get('display_errors')) {
            self::display($data);
        }
    }

    private static $count=0;

    public static function esc($s) {
        return htmlspecialchars($s, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
    }    
    public static function display($data) { //$file, $line, $code, $message, $escape, $title, $group='', $type='') {

        extract($data);

        $file = self::esc($file);
        $code = self::esc($code);
        if ($escape) {
            $message = self::esc($message);
        }

        $message = nl2br($message, false);

        $typeClass = substr($type,0,2)==='E_' ? 'tplus-legacy' : 'tplus-throw';

        if ( ! self::$count) {
            self::$count++;
?>

<style>
 
.tplus {
    display: grid;
    grid-template-columns: 70px 1fr;
    gap: 3px;
    font:14px consolas,mono;
    color:#555;
    background: #ddd;
    padding:3px;
    margin:6px 3px;
    border-radius:3px;
  }
.tplus > div {
    padding:3px 7px;
}
.tplus > div:first-child { /* title */
    grid-column: span 2;
    font:bold 13px tahoma,verdana;
    padding:3px 3px;
}
.tplus > div:nth-child(even) { /* left column */
    text-align:right;
    padding: 3px 3px;
}
.tplus > div:nth-child(even) + div {  /* right column */
    border-radius:2px;
    background:#f2f2f2;
}
.tplus > div:nth-child(3) > span {
    font-weight:bold;
    background:#fff;
    padding:0px 3px;border-radius:2px;
}
.tplus-legacy {
    color:#0a3;
}
.tplus-throw {
    color:#e33;
}

</style>

<?php
        }

?>
<div class="tplus">
    <div><span><?=$title?></span></div>
    <div>Type</div><div><span class="<?=$typeClass?>"><?=$type?></span></div>
    <div>File</div><div><?=$file?></div>
    <div>Line</div><div><?=$line?></div>
    <div>Code</div><div><?=$code?></div>
    <div>Message</div><div><?=$message?></div>
</div>


<?php 
    }
}