<?php

namespace Civi\Standalone\AppSettings;

use Civi\Standalone\AppSettings;

class DsnLoader {

  public static function resolveDsn(string $prefix) {
    if (AppSettings::get($prefix . '_DSN')) {
      // if dsn is already set, ignore component values
      return;
    }

    $db = [];

    foreach (['HOST', 'NAME', 'USER', 'PASS', 'PORT'] as $componentKey) {
      $value = AppSettings::get($prefix . '_DB_' . $componentKey);
      if (!$value) {
        // missing a required key to compose the dsn - so give up
        // (note: defaults should be set in self::CONSTANTS and returned by get)
        return;
      }
      $db[$componentKey] = $value;
    }

    $dsn = "mysql://{$db['USER']}:{$db['PASS']}@{$db['HOST']}:{$db['PORT']}/{$db['NAME']}?new_link=true";
    $ssl = AppSettings::get($prefix . '_DB_SSL');
    if ($ssl) {
      $dsn .= '&' . $ssl;
    }
    AppSettings::set($prefix . '_DSN', $dsn);
  }

}
