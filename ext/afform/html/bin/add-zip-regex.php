#!/usr/bin/php
<?php

## This script allows you to add to a ZIP file -- while filtering the filename.
## For example, suppose you want to inject a prefix to put everything in a subdir;
## match the start of the name (^) and put in the new sub dir:
##
## find -type f | add-zip-regex.php myfile.zip :^: 'the-new-sub-dir/'

if (PHP_SAPI !== 'cli') {
  die("This tool can only be run from command line.");
}

if (empty($argv[1]) || empty($argv[2])) {
  die(sprintf("usage: cat files.txt | %s <zipfile> <old-prefix> <new-prefix>\n", $argv[0]));
}

$zip = new ZipArchive();
$zip->open($argv[1], ZipArchive::CREATE);
$zip->addEmptyDir($argv[3]);

$files = explode("\n", file_get_contents('php://stdin'));
foreach ($files as $file) {
  if (empty($file)) {
    continue;
  }
  $file = preg_replace(':^\./:', '', $file);
  $internalName = preg_replace($argv[2], $argv[3], $file);
  if (file_exists($file) && is_dir($file)) {
    $zip->addEmptyDir($internalName);
  }
  else {
    $zip->addFile($file, $internalName);
  }
}

$zip->close();
