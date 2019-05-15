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

echo "Changing version from $oldVersion to $newVersion...\n";

$verName = makeVerName($newVersion);
$phpFile = initFile("CRM/Upgrade/Incremental/php/{$verName}.php", function () use ($verName) {
  ob_start();
  global $camelNumber;
  $camelNumber = $verName;
  require 'CRM/Upgrade/Incremental/php/Template.php';
  unset($camelNumber);
  return ob_get_clean();
});

$sqlFile = initFile("CRM/Upgrade/Incremental/sql/{$newVersion}.mysql.tpl", function () use ($newVersion) {
  return "{* file to handle db changes in $newVersion during upgrade *}\n";
});

updateFile("xml/version.xml", function ($content) use ($newVersion, $oldVersion) {
  return str_replace($oldVersion, $newVersion, $content);
});

if (file_exists("civicrm-version.php")) {
  updateFile("civicrm-version.php", function ($content) use ($newVersion, $oldVersion) {
    return str_replace($oldVersion, $newVersion, $content);
  });
}

updateFile("sql/civicrm_generated.mysql", function ($content) use ($newVersion, $oldVersion) {
  return str_replace($oldVersion, $newVersion, $content);
});

if ($doCommit) {
  $files = "xml/version.xml sql/civicrm_generated.mysql " . escapeshellarg($phpFile) . ' ' . escapeshellarg($sqlFile);
  passthru("git add $files");
  passthru("git commit $files -m " . escapeshellarg("Set version to $newVersion"));
}

/* *********************************************************************** */
/* Helper functions */

/**
 * Update the content of a file.
 *
 * @param string $file
 * @param callable $callback
 *   Function(string $originalContent) => string $newContent.
 */
function updateFile($file, $callback) {
  if (!file_exists($file)) {
    die("File does not exist: $file\n");
  }
  echo "Update \"$file\"\n";
  $content = file_get_contents($file);
  $content = $callback($content);
  file_put_contents($file, $content);
}

/**
 * Initialize a file (if it doesn't already exist).
 * @param string $file
 * @param callable $callback
 *   Function() => string $newContent.
 */
function initFile($file, $callback) {
  if (file_exists($file)) {
    echo "File \"$file\" already exists.\n";
  }
  else {
    echo "Initialize \"$file\"\n";
    $content = $callback();
    file_put_contents($file, $content);
  }
  return $file;
}

/**
 * Render a pretty string for a major/minor version number.
 *
 * @param string $version
 *   Ex: '5.10.alpha1'
 * @return string
 *   Ex: 'FiveTen'.
 */
function makeVerName($version) {
  list ($a, $b) = explode('.', $version);
  require_once 'CRM/Utils/EnglishNumber.php';
  return CRM_Utils_EnglishNumber::toCamelCase($a) . CRM_Utils_EnglishNumber::toCamelCase($b);
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
