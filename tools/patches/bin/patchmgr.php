#!/usr/bin/env php
<?php

if (PHP_SAPI !== 'cli') {
  die("This tool can only be run from command line.");
}

define('PROJECT_DIR', dirname(__DIR__, 3));
define('CDN_PREFIX', 'https://download.civicrm.org/patches');
define('RAW_DIR', PROJECT_DIR . '/tools/patches/raw');
define('DIST_DIR', PROJECT_DIR . '/tools/patches/dist');
define('COMPOSER_FILE', PROJECT_DIR . '/composer.json');

function main($argv) {
  if (count($argv) < 2) {
    fwrite(STDERR, help());
    exit(1);
  }

  $command = $argv[1];
  switch ($command) {
    case 'help':
      echo help();
      break;

    case 'use-local':
      useLocal();
      break;

    case 'use-remote':
      useRemote();
      break;

    case 'build':
      build();
      break;

    case 'validate':
      validate();
      break;

    // case 'download':
    //   downloadPatches();
    //   break;

    default:
      exit("Unknown command: $command\n");
  }
}

/**
 * Generate help text
 *
 * @return string
 *   Full help text
 */
function help(): string {
  // return "Usage: patchmgr <use-local|use-remote|build>\n";
  $rawDir = RAW_DIR;
  $distDir = DIST_DIR;
  $composer = COMPOSER_FILE;
  $cdnPrefix = CDN_PREFIX;
  return <<<EOHELP
SUMMARY: Use patchmgr to update the patch-list in composer.json and ./tools/patches.
USAGE: patchmgr <use-local|use-remote|build>

KEY PATHS:

   Composer config:     $composer
   Local patches:       $rawDir
   Publishable patches: $distDir
   CDN Prefix:          $cdnPrefix

COMMANDS:

  use-local    Update composer.json with a list of patch files, with local paths.
  use-remote   Update composer.json with a list of patch files, with remote URLs.
  build        Create a dist/ tree that can be published to CDN.
  validate     Assert that the patch-list in composer.json is suitable for redistribution.


EOHELP;
}

/**
 * Update `composer.json` with a list of local patch-files.
 */
function useLocal(): void {
  $patches = [];
  foreach (scanRawPatches() as $entry) {
    [$vendor, $package, $patchFile, $desc] = $entry;
    $path = "tools/patches/raw/$vendor/$package/$patchFile";
    $packageKey = "$vendor/$package";
    $patches[$packageKey][$desc] = $path;
  }
  sortPatches($patches);

  $composer = readComposer();
  $composer['extra']['patches'] = $patches;
  writeComposer($composer);
  echo "Update composer.json with local patch-files.\n";
}

/**
 * @return array
 */
function createRemotePatchList(): array {
  $patches = [];
  foreach (scanRawPatches() as $entry) {
    [$vendor, $package, $patchFile, $desc] = $entry;
    $path = "tools/patches/raw/$vendor/$package/$patchFile";
    $checksum = hash_file('sha256', $path);
    $url = CDN_PREFIX . '/' . $checksum . '/' . $patchFile;
    $packageKey = "$vendor/$package";
    $patches[$packageKey][$desc] = $url;
  }
  sortPatches($patches);
  return $patches;
}

/**
 * Update `composer.json` with a list of remote patch-URLs.
 */
function useRemote(): void {
  $patches = createRemotePatchList();

  $composer = readComposer();
  $composer['extra']['patches'] = $patches;
  writeComposer($composer);
  echo "Update composer.json with remote patch-URLs.\n";
}

/**
 * Assert that `composer.json` has the right list of remote patch-URLs.
 * @return void
 */
function validate(): void {
  $expectPatches = createRemotePatchList();

  $composer = readComposer();
  $actualPatches = $composer['extra']['patches'];
  sortPatches($actualPatches);

  $expectPatchesJson = PrettyJsonEncoder::encode($expectPatches);
  $actualPatchesJson = PrettyJsonEncoder::encode($actualPatches);

  if ($expectPatchesJson !== $actualPatchesJson) {
    echo "The composer.json has an incorrent list of patches.\n";
    echo "EXPECTED: $expectPatchesJson\n\n";
    echo "ACTUAL: $expectPatchesJson\n\n";
    exit(1);
  }
}

/**
 * Find all files in 'tools/patches/raw/'. Copy them to 'tools/patches/dist/'.
 */
function build(): void {
  $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(RAW_DIR));
  foreach ($rii as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.patch')) {
      $contents = file_get_contents($file->getPathname());
      $hash = hash('sha256', $contents);
      $destDir = DIST_DIR . "/$hash";
      if (!is_dir($destDir)) {
        mkdir($destDir, 0777, TRUE);
      }
      $destFile = $destDir . '/' . $file->getFilename();
      copy($file->getPathname(), $destFile);
      echo "Copied {$file->getFilename()} to $hash/\n";
    }
  }
}

function readComposer() {
  return json_decode(file_get_contents(COMPOSER_FILE), TRUE);
}

function writeComposer($data) {
  file_put_contents(COMPOSER_FILE, PrettyJsonEncoder::encode($data) . "\n");
}

function sortPatches(array &$patches): void {
  ksort($patches);
  foreach ($patches as &$patchList) {
    uasort($patchList, fn($a, $b) => strnatcmp(basename($a), basename($b)));
  }
}

// /**
//  * Walk the list of patch-URLs. Download them into the ./tools/patches/raw folder
//  */
// function downloadPatches(): void {
//   $composer = readComposer();
//   $patches = $composer['extra']['patches'] ?? [];
//
//   foreach ($patches as $package => $entries) {
//     if (!preg_match('#^([^/]+)/(.+)$#', $package, $matches)) {
//       echo "Skipping invalid package name: $package\n";
//       continue;
//     }
//     [$_, $vendor, $pkg] = $matches;
//
//     foreach ($entries as $desc => $url) {
//       $filename = basename(parse_url($url, PHP_URL_PATH));
//       $targetDir = RAW_DIR . "/$vendor/$pkg";
//       $patchPath = "$targetDir/$filename";
//       $descPath = "$targetDir/" . pathinfo($filename, PATHINFO_FILENAME) . ".txt";
//
//       if (!is_dir($targetDir)) {
//         mkdir($targetDir, 0777, TRUE);
//       }
//
//       // Download patch
//       if (str_starts_with($url, 'http')) {
//         $data = @file_get_contents($url);
//         if ($data === FALSE) {
//           echo "Failed to download: $url\n";
//           continue;
//         }
//       }
//       else {
//         $src = __DIR__ . '/' . ltrim($url, '/');
//         if (!file_exists($src)) {
//           echo "File not found: $src\n";
//           continue;
//         }
//         $data = file_get_contents($src);
//       }
//
//       file_put_contents($patchPath, $data);
//       file_put_contents($descPath, $desc);
//       echo "Exported $package/$filename\n";
//     }
//   }
// }

/**
 * Get a list of all the patch-files in ./tools/patches/raw.
 *
 * @return array
 */
function scanRawPatches() {
  $entries = [];
  $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(RAW_DIR));
  foreach ($rii as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.patch')) {
      $relative = str_replace(RAW_DIR . '/', '', $file->getPathname());
      $parts = explode('/', $relative);
      if (count($parts) < 3) {
        continue;
      }
      [$vendor, $package, $patchFile] = $parts;

      $baseName = pathinfo($patchFile, PATHINFO_FILENAME);
      $descFile = RAW_DIR . "/$vendor/$package/$baseName.txt";
      if (!file_exists($descFile)) {
        continue;
      }
      $desc = trim(file_get_contents($descFile));

      $entries[] = [$vendor, $package, $patchFile, $desc];
    }
  }
  return $entries;
}

class PrettyJsonEncoder {

  public static function encode($data, $indentLevel = 0): string {
    $indent = str_repeat('  ', $indentLevel);
    $nextIndent = str_repeat('  ', $indentLevel + 1);

    if (is_array($data) && !self::isAssocArray($data)) {
      $isAssoc = array_keys($data) !== range(0, count($data) - 1);
      $isFlatList = !$isAssoc && self::isScalarArray($data);

      if ($isFlatList) {
        $flat = self::encodeJsonFlat($data);
        if (strlen($flat) < 225) {
          return $flat;
        }
      }

      $items = [];
      foreach ($data as $key => $value) {
        $encodedKey = $isAssoc ? json_encode((string) $key, JSON_UNESCAPED_SLASHES) . ': ' : '';
        $encodedValue = self::encode($value, $indentLevel + 1);
        $items[] = $nextIndent . $encodedKey . $encodedValue;
      }

      $joined = implode(",\n", $items);
      return "[\n$joined\n$indent]";
    }
    elseif (is_array($data) && self::isAssocArray($data)) {
      $items = [];
      foreach ($data as $key => $value) {
        $encodedKey = json_encode((string) $key, JSON_UNESCAPED_SLASHES);
        $encodedValue = self::encode($value, $indentLevel + 1);
        $items[] = $nextIndent . $encodedKey . ': ' . $encodedValue;
      }

      $joined = implode(",\n", $items);
      return "{\n$joined\n$indent}";
    }
    else {
      return json_encode($data, JSON_UNESCAPED_SLASHES);
    }
  }

  private static function encodeJsonFlat(array $data): string {
    return '[' . implode(', ', array_map(
        fn($v) => json_encode($v, JSON_UNESCAPED_SLASHES),
        $data
      )) . ']';
  }

  private static function isScalarArray(array $array): bool {
    foreach ($array as $value) {
      if (!is_scalar($value) && $value !== NULL) {
        return FALSE;
      }
    }
    return TRUE;
  }

  private static function isAssocArray(array $arr): bool {
    return array_keys($arr) !== range(0, count($arr) - 1);
  }

}

main($argv);
