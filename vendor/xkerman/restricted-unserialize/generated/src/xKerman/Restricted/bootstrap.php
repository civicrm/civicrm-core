<?php

function xKerman_Restricted_bootstrap($classname)
{
    if (strpos($classname, 'xKerman_Restricted_') !== 0) {
        return false;
    }
    $sep = DIRECTORY_SEPARATOR;
    $namespace = explode('_', $classname);
    $filename = array_pop($namespace);
    $path = dirname(__FILE__) . "{$sep}{$filename}.php";
    if (file_exists($path)) {
        require_once $path;
    }
}

spl_autoload_register('xKerman_Restricted_bootstrap');
$sep = DIRECTORY_SEPARATOR;
require_once dirname(__FILE__) . "{$sep}function.php";
