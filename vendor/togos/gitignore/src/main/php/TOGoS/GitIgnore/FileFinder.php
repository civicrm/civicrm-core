<?php

class TOGoS_GitIgnore_FileFinder
{
	public function __construct(array $options) {
		// TODO: Validate options
		$this->ruleset = $options['ruleset'];
		$this->invertRulesetResult = $options['invertRulesetResult'];
		$this->defaultResult = $options['defaultResult'];
		$this->includeDirectories = $options['includeDirectories'];
		$this->callback = $options['callback'];
	}

	protected function match($rootDir, $f) {
		$result = $this->ruleset->match($f);
		if( $this->invertRulesetResult and $result !== null ) $result = !$result;
		return $result === null ? $this->defaultResult : $result;
	}
	
	protected function _findFiles($rootDir, $f) {
		if( preg_match('#/$#', $rootDir) ) throw new Exception("Root directory argument to _findFiles should not end with a slash; given «{$rootDir}»");
		if( preg_match('#^/|/$#', $f) ) throw new Exception("Relative path argument to _findFiles should not start or end with a slash; given «{$f}»");
		//echo "_findFiles($rootDir, $f)\n";
		$fullPath = $f == '' ? $rootDir : $rootDir.'/'.$f;
		if( $this->includeDirectories or !is_dir($fullPath) ) {
			$result = $this->match($rootDir, $f);
			call_user_func($this->callback, $f, $result);
		}
		if( is_dir($fullPath) ) {
			$dh = opendir($fullPath);
			while( ($fn = readdir($dh)) !== false ) {
				if( $fn == '.' or $fn == '..' ) continue;
				$this->_findFiles($rootDir, $f == '' ? $fn : $f.'/'.$fn);
			}
			closedir($dh);
		}
	}
	
	public function findFiles($dir) {
		self::_findFiles($dir, '');
	}
}