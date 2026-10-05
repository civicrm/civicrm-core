#!/usr/bin/env php
<?php
$deletedFiles = [];
// This is as far back as the log reasonably needs to go; typically one major version less than
// CRM_Upgrade_Form::MINIMUM_UPGRADABLE_VERSION
$minVer = '4.6.0';

// Ignore deleted files in these directories.
// Because this list is primarily for consumption by sites that install
// from a zip file, tests and tools should not be there anyway.
$excludeDirectories = [
  'tests',
  'tools',
];

function parseLog($logString, &$deletedFiles, $prefix = '', $trackedFiles = [], $trackedDirs = []) {
  global $excludeDirectories;
  $log = preg_split("/\r\n|\n|\r/", $logString);
  foreach ($log as $line) {
    $fileName = NULL;
    $deleted = $renamed = [];
    preg_match('#^[ ]*delete[ ]+mode[ ]+[0-9]+[ ]+([^ ]+)#', $line, $deleted);
    if (isset($deleted[1])) {
      $fileName = $deleted[1];
    }
    else {
      preg_match('#^[ ]*rename[ ]+(.+)[ ]+\(\d+%\)#', $line, $renamed);
    }
    // Renamed files will look something like `rename CRM/Foo/{OldName.php => NewName.php}`
    // Moved files will look something like `rename CRM/Foo/{OldDir => NewDir}/File.php
    if (isset($renamed[1]) && str_contains($renamed[1], '{')) {
      $fileName = preg_replace('#{(.*) =>[^}]+}#', '$1', $renamed[1]);
      // If the file was moved up a directory, get rid of the extra slash
      $fileName = str_replace('//', '/', $fileName);
    }
    // Root-level renamed files won't have any curly-brace stuff so just capture the old name
    elseif (isset($renamed[1])) {
      $fileName = explode(' ', $renamed[1])[0];
    }
    // No filename or name doesn't make sense
    if (!$fileName || $fileName === '1') {
      continue;
    }
    // Exclude files from $excludeDirectories
    foreach ($excludeDirectories as $excludeDirectory) {
      if (!$prefix && (str_starts_with($fileName, "$excludeDirectory/"))) {
        continue 2;
      }
    }
    $fullPath = $prefix . $fileName;
    if (!isset($trackedFiles[$fullPath])) {
      // Was the file deleted or was the entire directory deleted?
      $path = explode('/', $fullPath);
      array_pop($path);
      $removedDir = !isset($trackedDirs[implode('/', $path)]);
      if ($removedDir) {
        // Recurse up to the top-level deleted directory
        do {
          $dir = array_pop($path);
        }
        while ($path && $dir && !isset($trackedDirs[implode('/', $path)]));
        if ($dir) {
          $deletedFiles[] = implode('/', $path) . ($path ? '/' : '') . "$dir/*";
        }
        else {
          $removedDir = FALSE;
        }
      }
      if (!$removedDir) {
        $deletedFiles[] = $fullPath;
      }
    }
  }
}

/**
 * Case-sensitive lookup of tracked files and directories from git.
 *
 * Normalizes the inconsistency between Mac/Win (insensitive) and Linux (sensitive).
 */
function getTrackedTree($prefix = '') {
  $cmd = $prefix ? "(cd " . escapeshellarg($prefix) . " && git ls-files)" : "git ls-files";
  $output = `$cmd`;
  $files = [];
  $dirs = [];
  if ($output) {
    foreach (preg_split("/\r\n|\n|\r/", trim($output)) as $file) {
      if (!$file) {
        continue;
      }
      $fullPath = $prefix . $file;
      $files[$fullPath] = TRUE;
      $parts = explode('/', $fullPath);
      array_pop($parts);
      while ($parts) {
        $dirs[implode('/', $parts)] = TRUE;
        array_pop($parts);
      }
    }
  }
  return [$files, $dirs];
}

// Core files
[$trackedFiles, $trackedDirs] = getTrackedTree();
$logString = `git log $minVer...HEAD --diff-filter=DR --summary | grep '\(delete\|rename\)'`;
parseLog($logString, $deletedFiles, '', $trackedFiles, $trackedDirs);

// Packages
$prefix = 'packages/';
if (is_dir($prefix . '.git')) {
  [$packagesTrackedFiles, $packagesTrackedDirs] = getTrackedTree($prefix);
  $logString = `(cd $prefix && git log $minVer...HEAD --diff-filter=DR --summary | grep '\(delete\|rename\)')`;
  parseLog($logString, $deletedFiles, $prefix, $packagesTrackedFiles, $packagesTrackedDirs);
}

// Vendor: these files are managed by composer not git.
// for lack of anything more clever here's a hand-curated list.
$deletedFiles[] = 'vendor/pear/net_smtp/examples/*';
$deletedFiles[] = 'vendor/pear/net_smtp/tests/*';
$deletedFiles[] = 'vendor/pear/net_smtp/phpdoc.sh';
$deletedFiles[] = 'vendor/phpoffice/phpword/samples/*';

$deletedFiles = array_unique($deletedFiles);
sort($deletedFiles);
$fileName = 'deleted-files-list.json';
$fileCount = count($deletedFiles);
file_put_contents(__DIR__ . "/../../$fileName", json_encode($deletedFiles, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
echo "Wrote $fileCount items to '$fileName'.\n";
