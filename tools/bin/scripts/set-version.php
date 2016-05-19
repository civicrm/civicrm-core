#!/usr/bin/env php
<?php

// Update the data-files within this repo to reflect a new version number.
// Example usage:
//   git checkout origin/master -b master-4.7.29
//   ./tools/bin/scripts/set-version.php 4.7.29 --commit
//   git commit -m "Update to version 4.7.29"
//   git push origin master

/* *********************************************************************** */
/* Boot */

$civicrm_root = dirname(dirname(dirname(__DIR__)));
chdir($civicrm_root);

/* *********************************************************************** */
/* Parse inputs -- $oldVersion, $newVersion, $doCommit */

$oldVersion = (string) simplexml_load_file("xml/version.xml")->version_no;
if (!isVersionValid($oldVersion)) {
  fatal("failed to read old version from \"xml/version.xml\"\n");
}

$newVersion = @$argv[1];
if (!isVersionValid($newVersion)) {
  fatal("failed to read new version\n");
}

switch (@$argv[2]) {
  case '--commit':
    $doCommit = 1;
    break;
  case '--no-commit':
    $doCommit = 0;
    break;
  default:
    fatal("Must specify --commit or --no-commit\n");
}

/* *********************************************************************** */
/* Main */

echo "Updating from $oldVersion to $newVersion...\n";

updateFile("xml/version.xml", function ($content) use ($newVersion, $oldVersion) {
  return str_replace($oldVersion, $newVersion, $content);
});

updateFile("sql/civicrm_generated.mysql", function ($content) use ($newVersion, $oldVersion) {
  return str_replace($oldVersion, $newVersion, $content);
});

$sqlFile = "CRM/Upgrade/Incremental/sql/{$newVersion}.mysql.tpl";
if (!file_exists($sqlFile)) {
  echo "Create \"$sqlFile\"\n";
  file_put_contents($sqlFile, "{* file to handle db changes in $newVersion during upgrade *}\n");
}

if ($doCommit) {
  $files = "xml/version.xml sql/civicrm_generated.mysql " . escapeshellarg($sqlFile);
  passthru("git add $files");
  passthru("git commit $files -m " . escapeshellarg("Set version to $newVersion"));
}

/* *********************************************************************** */
/* Helper functions */

function updateFile($file, $callback) {
  if (!file_exists($file)) {
    die("File does not exist: $file\n");
  }
  echo "Update \"$file\"\n";
  $content = file_get_contents($file);
  $content = $callback($content);
  file_put_contents($file, $content);
}

function isVersionValid($v) {
  return $v && preg_match('/^[0-9a-z\.\-]+$/', $v);
}

/**
 * @param $error
 */
function fatal($error) {
  echo $error;
  echo "usage: set-version.php <new-version> [--commit|--no-commit]\n";
  echo "  With --commit, any changes will be committed automatically the current git branch.\n";
  echo "  With --no-commit, any changes will be left uncommitted.\n";
  exit(1);
}
