<?php

class TOGoS_GitIgnore_Util
{
	public static function aize($word) {
		if( preg_match('/^[aeiou]/', $word) ) return "an $word";
		return "a $word";
	}
	
	public static function describe($val) {
		if( $val === null ) return "null";
		if( is_float($val) or is_int($val) ) return "the number $val";
		if( is_bool($val) ) return $val ? "true" : "false";
		if( is_object($val) ) return self::aize(get_class($val));
		return aize(gettype($val));
	}
}
