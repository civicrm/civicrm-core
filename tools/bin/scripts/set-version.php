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
if (!(php_sapi_name() == 'cli' || (is_numeric($_SERVER['argc']) && $_SERVER['argc'] > 0))) {
  header("HTTP/1.0 404 Not Found");
  return;
}
$civicrm_root = dirname(dirname(dirname(__DIR__)));
chdir($civicrm_root);

/* *********************************************************************** */
/* Parse inputs -- $oldVersion, $newVersion, $doCommit */

$oldVersion = (string) simplexml_load_file("xml/version.xml")->version_no;
if (!isVersionValid($oldVersion)) {
  fatal("failed to read old version from \"xml/version.xml\"\n");
}

/**
 * @var string $newVersion */
/**
 * @var bool $doCommit */
/**
 * @var bool $doSql */
extract(parseArgs($argv));

if (!isVersionValid($newVersion)) {
  fatal("failed to read new version\n");
}

/* *********************************************************************** */
/* Main */

echo "Changing version from $oldVersion to $newVersion...\n";

$verName = makeVerName($newVersion);
$phpFile = initFile("CRM/Upgrade/Incremental/php/{$verName}.php", function () use ($verName, $newVersion) {
  ob_start();
  global $camelNumber;
  global $versionNumber;
  $camelNumber = $verName;
  $versionNumber = $newVersion;
  require 'CRM/Upgrade/Incremental/php/Template.php';
  unset($camelNumber, $versionNumber);
  return ob_get_clean();
});

// It is typical for `*.alpha` to need SQL file -- and for `*.beta1` and `*.0` to NOT need a SQL file.
if ($doSql === TRUE || ($doSql === 'auto' && preg_match(';alpha;', $newVersion))) {
  $sqlFile = initFile("CRM/Upgrade/Incremental/sql/{$newVersion}.mysql.tpl", function () use ($newVersion) {
    return "{* file to handle db changes in $newVersion during upgrade *}\n";
  });
}
else {
  $sqlFile = NULL;
}

updateXmlFile("xml/version.xml", function(DOMDocument $dom) use ($newVersion, $releaseDate) {
  foreach ($dom->getElementsByTagName('version_no') as $tag) {
    $tag->textContent = $newVersion;
  }
  $date = preg_match('/(alpha|beta)/', $newVersion) ? '(unreleased)' : $releaseDate;
  foreach ($dom->getElementsByTagName('releaseDate') as $tag) {
    $tag->textContent = $date;
  }
});

if (file_exists("civicrm-version.php")) {
  updateFile("civicrm-version.php", function ($content) use ($newVersion, $oldVersion) {
    return str_replace($oldVersion, $newVersion, $content);
  });
}

updateFile("sql/civicrm_generated.mysql", function ($content) use ($newVersion, $oldVersion) {
  return str_replace($oldVersion, $newVersion, $content);
});

updateFile("sql/test_data_second_domain.mysql", function ($content) use ($newVersion, $oldVersion) {
  return str_replace($oldVersion, $newVersion, $content);
});

updateFile("js/version.json", function () use ($newVersion) {
  return json_encode($newVersion) . "\n";
});

// Update deleted-files-list.json
`php tools/scripts/generate-deleted-files-list.php`;

if ($doCommit) {
  $files = array_filter(
    array_merge(['xml/version.xml', 'js/version.json', 'deleted-files-list.json', 'sql/civicrm_generated.mysql', 'sql/test_data_second_domain.mysql', $phpFile, $sqlFile]),
    function($file) {
      return $file && file_exists($file);
    }
  );
  $filesEsc = implode(' ', array_map('escapeshellarg', $files));
  passthru("git add $filesEsc");
  passthru("git commit $filesEsc -m " . escapeshellarg("Set version to $newVersion"));
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
 * Update the content of an XML file
 *
 * @param string $file
 * @param callable $callback
 *   Function(DOMDocument $dom)
 */
function updateXmlFile($file, $callback) {
  updateFile($file, function ($content) use ($callback) {
    $dom = new DomDocument();
    $dom->loadXML($content);
    $dom->preserveWhiteSpace = FALSE;
    $dom->formatOutput = TRUE;
    $callback($dom);
    return $dom->saveXML();
  });
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
  [$a, $b] = explode('.', $version);
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
  echo "usage: set-version.php <new-version> [<new-date>] [--sql|--no-sql] [--commit|--no-commit]\n";
  echo "  --sql        A placeholder *.sql file will be created.\n";
  echo "  --no-sql     A placeholder *.sql file will not be created.\n";
  echo "  --commit     Any changes will be committed automatically the current git branch.\n";
  echo "  --no-commit  Any changes will be left uncommitted.\n";
  echo "\n";
  echo "If the SQL style is not specified, it will decide automatically. (Alpha versions get SQL files.)\n";
  echo "\n";
  echo "You MUST indicate whether to commit.\n";
  exit(1);
}

/**
 * @param array $argv
 *  Ex: ['myscript.php', '--no-commit', '5.6.7']
 * @return array
 *   Ex: ['scriptFile' => 'myscript.php', 'doCommit' => FALSE, 'newVersion' => '5.6.7', 'releaseDate' => '2039-01-01']
 */
function parseArgs($argv) {
  $parsed = [];
  $parsed['doSql'] = 'auto';
  $parsed['releaseDate'] = date('Y-m-d');
  $positions = ['scriptFile', 'newVersion', 'releaseDate'];
  $positional = [];

  foreach ($argv as $arg) {
    switch ($arg) {
      case '--commit':
        $parsed['doCommit'] = TRUE;
        break;

      case '--no-commit':
        $parsed['doCommit'] = FALSE;
        break;

      case '--sql':
        $parsed['doSql'] = TRUE;
        break;

      case '--no-sql':
        $parsed['doSql'] = FALSE;
        break;

      default:
        if ($arg[0] !== '-') {
          $positional[] = $arg;
        }
        else {
          fatal("Unrecognized argument: $arg\n");
        }
        break;
    }
  }

  foreach ($positional as $offset => $value) {
    $name = $positions[$offset] ?? "unknown_$offset";
    $parsed[$name] = $value;
  }

  if (!isset($parsed['doCommit'])) {
    fatal("Must specify --commit or --no-commit\n");
  }

  return $parsed;
}
