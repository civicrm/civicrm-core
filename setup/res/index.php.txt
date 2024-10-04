<?php

/**
 * Civicrm Standalone index.php
 *
 * This file just locates the civicrm.standalone.php file, and then runs the Webloader
 */

// civicrm.standalone.php in same directory
$appRootPath = __DIR__;

# // alternative: if you use a web subdirectory, the app root will be up one directory
# $appRootPath = implode(DIRECTORY_SEPARATOR, [__DIR__, '..']);

# // alternative: search up the directory tree until we find a civicrm.standalone.php file
# function findStandaloneBootFile() {
#   $path = explode(DIRECTORY_SEPARATOR, __DIR__);
#
#   while ($path) {
#     $bootFile = implode(DIRECTORY_SEPARATOR, [...$path, 'civicrm.standalone.php']);
#
#     if (file_exists($bootFile)) {
#       return $path;
#     }
#     array_pop($path);
#   }
#
#   throw new \Error('Couldn\'t find civicrm.standalone.php in ' . __DIR__ . ' or any of its parent directories');
# }
#
# $appRootPath = findStandaloneBootFile();

require_once implode(DIRECTORY_SEPARATOR, [$appRootPath, 'civicrm.standalone.php']);

\Civi\Standalone\WebEntrypoint::index();
