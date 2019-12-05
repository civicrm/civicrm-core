<?php

class TOGoS_GitIgnore_Ruleset
{
	protected $rules;
	
	public function addRule($rule) {
		if( is_string($rule) ) {
			$str = trim($rule);
			if( $str == '' ) return;
			if( $str[0] == '#' ) return;
			if( substr($str,0,2) == '\\#' ) $str = substr($str,1);
			$rule = TOGoS_GitIgnore_Rule::parse($str);
		}
		if( !($rule instanceof TOGoS_GitIgnore_Rule) ) {
			throw new Exception("Argument to TOGoS_GitIgnore_Ruleset#addRule should be a string or TOGoS_GitIgnore_Rule; received ".TOGoS_GitIgnore_Util::describe($rule));
		}
		$this->rules[] = $rule;
	}

	public function match($path) {
		if( !is_string($path) ) {
			throw new Exception(__METHOD__." expects a string; given ".TOGoS_GitIgnore_Util::describe($path));
		}
		$lastResult = null;
		foreach( $this->rules as $rule ) {
			$result = $rule->match($path);
			if( $result !== null ) $lastResult = $result;
		}
		return $lastResult;
	}

	public static function loadFromStrings($lines) {
		$rs = new self;
		foreach( $lines as $line ) $rs->addRule($line);
		return $rs;
	}
	
	public static function loadFromString($str) {
		$lines = explode("\n", $str);
		return self::loadFromStrings($lines);
	}
	
	public static function loadFromFile($filename) {
		$rs = new self;
		$fh = fopen($filename);
		while( ($line = fgets($fh)) ) $rs->addRule($line);
		fclose($fh);
		return $rs;
	}
}
