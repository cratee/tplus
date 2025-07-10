<?php

include_once dirname(__file__).'/Tplus.php';

class Tpl {	
	protected static function config() {
		return [
			/**
			 *  (1/4) template directory 
			 */
			'HtmlRoot' => './html/',

			/**
			 *  (2/4) template script directory 
			 * 		must be writable (rwx) by the web server. 
			 */
    		'HtmlScriptRoot' => './html.php/',

			/**
			 *  (3/4) script check
			 * 		true : check template file and compile if necessary.
			 * 		false: skip template file check and use compiled script file only.
			 * 
			 * 		TIP: You can use your own Logic for environment detection.
			 * 		e.g. 'ScriptCheck' => $GLOBALS['server_mode']=='development' ? true : false;
			 */
			'ScriptCheck' => true,

			/**
			 *  (4/4) assign check
			 * 		Set to 'false' to ignore unassigned Tplus variables
			 * 		This suppreses E_NOTICE (PHP 7.x) or E_WARNING (PHP 8.x).
			 */
			'AssignCheck' => true
		];
	}
	public static function get($path, $data=[]) {
		$_ = self::_();
		$_->assign($data);
		return $_->fetch($path);
	}
	public static function _() {
		return new Tplus(static::config());
	}
}

class TplWrapper extends TplusWrapper {

	protected $x;

	public function double() {
		return $this->x * 2;
	}
	public function average() {
		if (is_array($this->x)) {
			return array_sum($this->x) / count($this->x);
		}
	}
	public function shuffle() {
		if (is_array($this->x)) {
			shuffle($this->x);
			return $this->x;
		}
		return str_shuffle((string)$this->x);
	}
}

class TplLoopHelper extends TplusLoopHelper {

	protected $i;
	protected $s;
	protected $k;
	protected $v;

	public function isEven() {
		return $this->i % 2 ? true : false;
	}
	public function isLast() {
		return $this->i + 1 == $this->s;
	}
}