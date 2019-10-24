#!/usr/bin/env php
<?php
eval(`cv php:boot`);

global $civicrm_root;
$realArgs = $argv;
$diffCmd = FALSE;
$files = [];

array_shift($realArgs);
foreach ($realArgs as $arg) {
  switch ($arg) {
    case '--diff':
      $diffCmd = 'diff -wu';
      break;

    case '--colordiff':
      $diffCmd = 'colordiff -wu';
      break;

    default:
      $files[] = $arg;
  }
}

if (empty($files)) {
  echo "usage: cleanup-angular.php [--diff|--colordiff] <file>\n";
  echo "example: cleanup-angular.php '/full/path/to/crmMailing/BlockSummary.html'\n";
  echo "note: The file path must be absolute.\n";
  exit(1);
}
else {
  foreach ($files as $file) {
    compareFile($file, $diffCmd);
  }
}

function compareFile($file, $diffCmd) {
  $coder = new \Civi\Angular\Coder();

  if (!file_exists($file)) {
    fwrite(STDERR, "Failed to find file $file (CWD=" . getcwd() . ")\n");
    return;
  }
  $oldMarkup = file_get_contents($file);
  if ($coder->checkConsistentHtml($oldMarkup)) {
    echo "File \"$file\" appears sufficiently consistent.\n";
  }
  else {
    $newMarkup = $coder->recode($oldMarkup);
    $newFile = "{$file}.recoded";
    echo "File \"$file\" appears to have consistency issues. Created $newFile.\n";
    file_put_contents($newFile, $newMarkup);
    if ($diffCmd) {
      passthru($diffCmd . ' ' . escapeshellarg($file) . ' ' . escapeshellarg($newFile));
    }
  }
}
