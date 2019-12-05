<?php

class TOGoS_GitIgnore_Pattern
{
	protected $patternString;
	protected $regex;
	protected function __construct($pattern, $regex) {
		$this->patternString = $pattern;
		$this->regex = $regex;
	}

	public function getPatternString() {
		return $this->patternString;
	}

	protected static function patternToRegex($pp) {
		preg_match_all('/\*|\*\*|\?|[^\*\?]|\[![^\]+]|\[[^\]+]/', $pp, $bifs);
		$regex = '';
		foreach( $bifs[0] as $part ) {
			if( $part == '**' ) $regex .= ".*";
			else if( $part == '*' ) $regex .= "[^/]*";
			else if( $part == '?' ) $regex .= '?';
			else if( $part[0] == '[' ) {
				// Not exactly, but maybe close enough.
				// Maybe fnmatch is the thing to use
				if( $part[1] == '!' ) $part[1] = '^';
				$regex .= $part;
			}
			else $regex .= preg_quote($part, '#');
		}
		return $regex;
	}

	public static function parse($pattern) {
		$r = self::patternToRegex($pattern);
		if( strlen($pattern) == 0 ) {
			throw new Exception("Zero-length pattern string passed to ".__METHOD__);
		}
		if( $pattern[0] == '/' ) {
			$r = '#^'.substr($r,1).'(?:$|/)#';
		} else {
			$r = '#(?:^|/)'.$r.'(?:$|/)#';
		}
		return new self($pattern, $r);
	}

	public function match($path) {
		if( strlen($path) > 0 and $path[0] == '/' ) {
			throw new Exception("Paths passed to #match should not start with a slash; given: «".$path."»");
		}
		if( !is_string($path) ) {
			throw new Exception(__METHOD__." expects a string; given ".TOGoS_GitIgnore_Util::describe($path));
		}
		return preg_match($this->regex, $path);
	}
}
