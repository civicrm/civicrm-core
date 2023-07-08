<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * Send a static file.
 *
 * @param string $path
 * @return bool
 */
function send_file(string $path): bool {
  header('Content-Type: '.mime_content_type($path));
  $fh = fopen($path, 'r');
  fpassthru($fh);
  fclose($fh);
  return TRUE;
}

$civicrm_root = dirname(__DIR__, 2);

if (preg_match(';^/core/;', $_SERVER['REQUEST_URI'])) {
  $file = $civicrm_root . substr($_SERVER['REQUEST_URI'], 5);
  if (strpos($_SERVER['REQUEST_URI'], '..') !== FALSE) {
    $realFile = realpath($file);
    if (strpos($realFile, $civicrm_root) !== 0) {
      http_send_status(403);
      echo "Malformed path";
      return;
    }
  }
  return send_file($file);
}

echo $_SERVER['REQUEST_URI'];
