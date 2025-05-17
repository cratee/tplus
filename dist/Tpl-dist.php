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

	public static function get($path, $vals=[]) {
		$_ = self::_();
		$_->assign($vals);
		return $_->fetch($path);
	}

	public static function _() {

		return new Tplus(static::config());
	}
}


class TplValWrapper extends TplusValWrapper {

	protected $val;

	public function shuffle() {
		if (is_array($this->val)) {
			shuffle($this->val);
			return $this->val;
		}
		return str_shuffle((string)$this->val);
	}

	public function average() {
		if (is_array($this->val)) {
			return array_sum($this->val) / count($this->val);
		}
		throw new TplusRuntimeError(
			'average() method called on unsupported type '.gettype($this->val)
		);
	}
	
	public function format($decimals=0, $decimal_separator=".", $thousands_seperator=",") {
		if (is_array($this->val)) {
			return parent::iterate(__FUNCTION__, $decimals, $decimal_separator, $thousands_seperator);
		}
		return number_format($this->val, $decimals, $decimal_separator, $thousands_seperator);
	}
	public function double() {
		if (is_array($this->val)) {
			return parent::iterate(__FUNCTION__);
		}
		return $this->val * 2;
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
