<?php
namespace Civi\Setup;

class FileUtil {

  public static function isCreateable($file) {
    if (file_exists($file)) {
      return is_writable($file);
    }

    $next = dirname($file);
    do {
      $current = $next;
      if (file_exists($current)) {
        return is_writable($current);
      }
      $next = dirname($current);
    } while ($current && $next && $current != $next);

    return FALSE;
  }

  public static function makeWebWriteable($path) {
    // Blerg: Setting world-writable works as a default, but
    // it 'sprone to break systems that rely on umask's or facl's.
    chmod($path, 0777);
  }

  public static function isDeletable($path) {
    return is_writable(dirname($path));
  }

}
