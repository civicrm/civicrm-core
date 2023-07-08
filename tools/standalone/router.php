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

class StandaloneRouter {

  const ALLOW_FILES = ';\.(jpg|png|css|js|html|txt|json|yml|xml|md)$;';

  public function main(): bool {
    if (preg_match(';^/core/packages(/.*);', $_SERVER['PHP_SELF'], $m)) {
      return $this->sendFileFromFolder($this->findPackages(), $m[1]);
    }
    if (preg_match(';^/core(/.*);', $_SERVER['PHP_SELF'], $m)) {
      return $this->sendFileFromFolder($this->findCore(), $m[1]);
    }
    else {
      return $this->invoke($_SERVER['REQUEST_URI']);
    }
  }

  /**
   * Send a static file.
   *
   * @param string $path
   *
   * @return bool
   */
  public function sendFile(string $path): bool {
    if (file_exists($path)) {
      header('Content-Type: ' . mime_content_type($path));
      $fh = fopen($path, 'r');
      fpassthru($fh);
      fclose($fh);
      return TRUE;
    }
    else {
      return $this->sendError(404, "File not found");
    }
    return TRUE;
  }

  public function sendFileFromFolder(string $basePath, string $relPath): bool {
    $absFile = $basePath . $relPath;
    if (!preg_match(static::ALLOW_FILES, $relPath)) {
      return $this->sendError(403, "File type not allowed");
    }
    if (strpos($_SERVER['REQUEST_URI'], '..') !== FALSE) {
      $realFile = realpath($absFile);
      if (strpos($realFile, $basePath) !== 0) {
        return $this->sendError(403, "Malformed path");
      }
    }
    return $this->sendFile($absFile);
  }

  public function sendError(int $code, string $message): bool {
    http_response_code($code);
    printf("<h1>HTTP %s: %s</h1>", $code, htmlentities($message));
    return TRUE;
  }

  public function invoke(string $uri) {
    echo "Invoke route: " . htmlentities($uri) . "<br>";
    return TRUE;
  }


  public function findCore(): string {
    return dirname(__DIR__, 2);
  }

  public function findPackages(): string {
    $core = $this->findCore();
    if (file_exists($core . '/packages')) {
      return $core . '/packages';
    }
    if (file_exists(dirname($core) . '/civicrm-packages')) {
      return dirname($core) . '/civicrm-packages';
    }
    throw new \RuntimeException("Failed to find civicrm-packages");
  }

}

(new StandaloneRouter())->main();
