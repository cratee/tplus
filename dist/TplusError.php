<?php

class TplusError {

    private static $hashes=[];

    public static function handle($e) {
        if (!$e) {
            return;
        }

        $hash = md5(json_encode($e));
        if (isset(self::$hashes[$hash])) return;
        self::$hashes[$hash] = true;

        $date = date('[Y-m-d H:i:s]');
        $phpMessage = "{$e['message']} in {$e['file']} on line {$e['line']}";

        [$file, $line, $code] = self::getTplErrorInfo($e['file'], $e['line']);

        $runtimeType = self::getErrorType($e['type']);

        if ($file) {
            if (ini_get('log_errors')) {
                error_log("{$date}[Tplus] {$file}:{$line} {$code} => {$phpMessage}");
            }
            if (ini_get('display_errors')) {
                TplusErrorToBrowser::display(
                    $file, 
                    $line, 
                    $code, 
                    $runtimeType, 
                    $phpMessage, 
                    "Tplus Runtime Error"
                );
            }
        } else {
            
            if (ini_get('log_errors')) {
                error_log($date.'[Tplus Runtime Debugging Fails]'.$phpMessage);
            }
            if (ini_get('display_errors')) {
                echo '[Tplus Runtime Debugging Fails] '.$phpMessage;
            }
        }
    }
    private static function getTplErrorInfo($phpFile, $phpLine) {

        $lines = @file($phpFile, FILE_IGNORE_NEW_LINES);

        $tplFile = '';

        if (preg_match('~Tplus(?:\s\S+){3}\s(.+?)\s\d+\s\*/~', $lines[0], $m)) {
            $tplFile = $m[1];
        }        

        if (preg_match('~/\*\s*(\{.*\})\s*\*/\s*\?>~', $lines[$phpLine-1], $m)) {
            $json = json_decode($m[1], true);
        }

        if ($tplFile and $json) {
            return [ $tplFile, $json['line'], $json['code']];
        }
        return [null, null, null];
    }

	private static function getErrorType($errorConst)
	{
		$list = [
			'Parse error' => [
				'E_PARSE',
			],
			'Fatal error' => [
				'E_ERROR',
				'E_CORE_ERROR',
				'E_COMPILE_ERROR',
				'E_USER_ERROR',
			],
			'Catchable fatal error' => [
				'E_RECOVERABLE_ERROR',
			],
			'Warning' => [
				'E_WARNING',
				'E_CORE_WARNING',
				'E_COMPILE_WARNING',
				'E_USER_WARNING',
			],
			'Notice' => [
				'E_NOTICE',
				'E_DEPRECATED',
				'E_USER_NOTICE',
				'E_USER_DEPRECATED'
			],
			'Strict standards' => [
				'E_STRICT'
			]
		];

		foreach ($list as $errorGroup => $erroConstNames)
		{
			foreach ($erroConstNames as $name)
			{
				if	( $errorConst == constant($name) )
				{
                    switch ($errorGroup) {
                        case 'Notice'  : $errorLevel = 1; break;
                        case 'Warning' : $errorLevel = 2; break;
                        default: $errorLevel = 3;
                    }
					return ['group'=>$errorGroup, 'name'=>$name, 'level'=>$errorLevel];
				}
			}
		}
	}
}

class TplusErrorToBrowser {
    private static $count=0;
    
    public static function display($file, $line, $code, $runtimeType, $message, $title) {
        $titleClass = $runtimeType ? 'tplus-error-title' : 'tplus-scripter-title';
        $message = str_replace("\n","<br />\n", htmlspecialchars($message));
        $messageTitle = $runtimeType ? 'PHP Message' : 'Message';
        $code = htmlspecialchars($code);

		//[$errorGroup, $erroConstName, $errorLevel] = self::getErrorType($errorConst);

        if ( ! self::$count) {
?>

<style>
.tplus-error-container {padding:3px}
.tplus-error {width:100%;background:#ddd;border-spacing:3px;border-collapse:separate;border-radius: 4px;}
.tplus-error td {border-radius: 2px;font:normal normal 13px tahoma, verdana, sans-serif;border:0px}
.tplus-error-item {text-align:right;background:#ddd;color:#888;padding:3px 4px;width:90px;vertical-align:top}
.tplus-error-item span{font:normal normal 12px consolas, monospace;background:#fb9;color:#e33;padding:1px 5px;text-align:center;border-radius:2px;float:left}
.tplus-error-content {background:#f2f2f2;padding:3px 10px;}
.tplus-error-content span {font-weight:bold}
.tplus-error-content code {font:12px consolas, monospace;margin:0 4px;padding:1px 4px;border-radius:2px;vertical-align:1px}
code.tplus-error-level1 {background:#262;color:#ada}
code.tplus-error-level2 {background:#38c;color:#def}
code.tplus-error-level3 {background:#c22;color:#fcc} 
code.tplus-error-code {padding:0px;margin:0px;font-size:14px;font-weight:bold;}
.tplus-error td.tplus-error-title {font-size:13px;font-weight:bold;color:#555; padding:3px 5px;}
.tplus-error td.tplus-scripter-title{font-size:13px;font-weight:bold;color:#c22; padding:2px}
.tplus-error td.tplus-scripter-title div{background:#16a;color:#def;padding:4px 5px;border-radius:2px;float:left}
</style>

<?php 
		}
?>

<div class="tplus-error-container">
<table class="tplus-error">
<tr>
    <td colspan="2" class="<?=$titleClass?>"><div><?=$title?></div></td>
</tr>
<?php if ($runtimeType) {?>
<tr>
    <td class="tplus-error-item">
        <span><?=++self::$count?></span> Type
    </td>
    <td class="tplus-error-content">
        <span><?=$runtimeType['group']?></span> 
        <code class="tplus-error-level<?=(int)$runtimeType['level']?>"><?=$runtimeType['name']?></code>
    </td>
</tr>
<?php }?>
<tr>
    <td class="tplus-error-item">File</td>
    <td class="tplus-error-content"><?=$file?></td>
</tr>
<tr>
    <td class="tplus-error-item">Line</td>
    <td class="tplus-error-content"><?=$line?></td>
</tr>
<?php if ($code) {?>
<tr>
    <td class="tplus-error-item">Code</td>
    <td class="tplus-error-content"><code class="tplus-error-code"><?=$code?></code></td>
</tr>
<?php }?>
<tr>
    <td class="tplus-error-item"><?=$messageTitle?></td><td class="tplus-error-content"><?=$message?></td>
</tr>
</table>
</div>

<?php 
    }


}