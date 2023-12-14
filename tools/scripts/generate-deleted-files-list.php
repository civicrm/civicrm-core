#!/usr/bin/env php
<?php
$deletedFiles = [];
// This is as far back as the log reasonably needs to go; typically one major version less than
// CRM_Upgrade_Form::MINIMUM_UPGRADABLE_VERSION
$minVer = '4.6.0';

function parseLog($logString, &$deletedFiles, $prefix = '') {
  $log = preg_split("/\r\n|\n|\r/", $logString);
  foreach ($log as $line) {
    $matches = [];
    preg_match('#delete[ ]+mode[ ]+[0-9]+[ ]+([^ ]+)#', $line, $matches);
    $fileName = $matches[1] ?? NULL;
    if ($fileName && $fileName !== '1' && !file_exists($prefix . $fileName)) {
      // Was the file deleted or was the entire directory deleted?
      $path = explode('/', $prefix . $fileName);
      array_pop($path);
      $removedDir = !is_dir(implode('/', $path));
      if ($removedDir) {
        // Recuse up to the top-level deleted directory
        do {
          $dir = array_pop($path);
        }
        while ($path && $dir && !is_dir(implode('/', $path)));
        if ($dir) {
          $deletedFiles[] = implode('/', $path) . ($path ? '/' : '') . "$dir/*";
        }
        else {
          $removedDir = FALSE;
        }
      }
      if (!$removedDir) {
        $deletedFiles[] = $prefix . $fileName;
      }
    }
  }
}

// Core files
$logString = `git log $minVer...HEAD --diff-filter=D --summary | grep delete`;
parseLog($logString, $deletedFiles);

// Packages
$prefix = 'packages/';
$logString = `(cd $prefix && git log $minVer...HEAD --diff-filter=D --summary | grep delete)`;
parseLog($logString, $deletedFiles, $prefix);

$deletedFiles = array_unique($deletedFiles);
sort($deletedFiles);
$fileName = 'deleted-files-list.json';
$fileCount = count($deletedFiles);
file_put_contents(__DIR__ . "/../../$fileName", json_encode($deletedFiles, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
echo "Wrote $fileCount items to '$fileName'.\n";
