<?php  
include '../dist/Tpl-dist.php';

echo \Tpl::get('lab.html', []);

exit;
$text = 'Uncaught Error: Call to a member function ewwew() on null in D:\Work\Tplus\test\html.php\index.html.php:571
Stack trace:
#0 D:\Work\Tplus\dist\Tplus.php(38): include()
#1 D:\Work\Tplus\dist\Tpl-dist.php(41): Tplus-&gt;fetch(&#039;index.html&#039;)
#2 D:\Work\Tplus\test\index.php(123): Tpl::get(&#039;index.html&#039;, Array)
#3 {main}
  thrown in D:\Work\Tplus\test\html.php\index.html.php on line 571';
echo $text = str_replace("\n","<br/>\n", $text);




error_reporting(E_ALL & ~E_WARNING);

$errorHandler = new ErrorHandler();

set_error_handler(function($type, $message, $file, $line ) use ($errorHandler)
{
	$errorHandler->handle($type, $message, $file, $line, debug_backtrace());
	
}, E_ALL);

register_shutdown_function(function() use ($errorHandler)
{
	if( $error = error_get_last() )
	{
		extract($error);	// type, message, file, line

		$errorHandler->handle($type, $message, $file, $line);
	}
});


class ErrorHandler
{
	private static $_count = 0;
	function handle($type, $message, $file, $line, $debug_backtrace = null)
	{
		$config = (object)[
			'display'	=> 1,
			'log'		=> 0,
		];

		list($summary, $const_name, $level) = $this->_classify($type);
		
		if ($config->display)
		{
			//echo "<br>\n<b>$error_type :</b> ".htmlspecialchars($message)." in <b>$file</b> on line <b>$line</b>\n<br>\n";
			$this->_display($level, $summary, $const_name, $message, $file, $line);
		}
		if ($config->log)
		{
			error_log(strip_tags("\n$summary : $message in $file on line $line"));
		}
		//print_r($debug_backtrace);
	}
	private function _display($level, $summary, $const_name, $message, $file, $line)
	{
		if ( ! self::$_count)		{
?>
<style>
.sss-error-container {padding:3px}
.sss-error {width:100%;background:#ddd;border-spacing:3px;border-collapse:separate;border-radius: 4px;}
.sss-error td {border-radius: 2px;font:13px tahoma, verdana;border:0px}
.sss-error-item {text-align:right;background:#ddd;color:#888;padding:3px 4px;width:90px;vertical-align:top}
.sss-error-item span{font:12px consolas, monospace;background:#fb9;color:#e33;padding:1px 5px;text-align:center;border-radius:2px;font-style:normal;float:left}
.sss-error-content {background:#f2f2f2;padding:3px 10px;}
.sss-error-content span {font-weight:bold}
.sss-error-content code {font-style:normal;color:#fff;font:12px consolas, monospace;margin:0 4px;padding:1px 4px;border-radius:2px;background:#ccc;vertical-align:1px}
code.sss-error-level0 {background:#c22;color:#fcc} 
code.sss-error-level1 {background:#da0;color:#ffd}
code.sss-error-level2 {background:#38c;color:#def}
</style>
<?php 
		}
?>
<div class="sss-error-container">
<table class="sss-error">
<tr><td class="sss-error-item"><span><?=++self::$_count?></span> Category</td><td class="sss-error-content"><span><?=$summary?></span> <code class="sss-error-level<?=(int)$level?>"><?=$const_name?></code></td></tr>
<tr><td class="sss-error-item">File</td><td class="sss-error-content"><?=$file?></td></tr>
<tr><td class="sss-error-item">Line</td><td class="sss-error-content"><?=$line?></td></tr>
<tr><td class="sss-error-item">Description</td><td class="sss-error-content"><?=htmlspecialchars($message)?></td></tr>
</table>
</div>
<?php 
	}
	private function _classify($type)
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
		$level = 0;
		foreach ($list as $summary => $const_names)
		{
			foreach ($const_names as $name)
			{
				if	( $type == constant($name) )
				{
					return [$summary, $name, ((int)$level/2)];
				}
			}
			$level++;
		}
	}
}

echo $qqqqqqqq;

echo $aaa->bbb();
?>

<?="a"?>
<?="b"?>

<?="a"?>

<?="b"?>
