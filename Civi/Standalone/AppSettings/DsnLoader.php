<?php

namespace Civi\Standalone\AppSettings;

use Civi\Standalone\AppSettings;

class DsnLoader {

  public static function resolveDsn(string $prefix) {
    $dsn = AppSettings::get($prefix . '_DSN');
    if ($dsn) {
      // if dsn is set explicitly, use this as the source of truth.
      // set the component parts in case anyone wants to AppSettings::get them
      $urlComponents = \parse_url($dsn);

      if (!$urlComponents) {
        // couldn't parse for some reason? give up
        return;
      }

      foreach (['user', 'pass', 'host', 'port'] as $componentKey) {
        $settingName = $prefix . '_DB_' . strtoupper($componentKey);
        $value = $urlComponents[$componentKey] ?? null;

        if ($value) {
            AppSettings::set($settingName, $value);
        }
      }

      // for db name we need to parse the path
      $settingName = $prefix . '_DB_NAME';

      $urlPath = $urlComponents['path'] ?? '';
      $dbName = trim($urlPath, '/');
      if ($dbName) {
        AppSettings::set($settingName, $dbName);
      }

      return;
    }

    $db = [];

    foreach (['HOST', 'NAME', 'USER', 'PASS', 'PORT'] as $componentKey) {
      $value = AppSettings::get($prefix . '_DB_' . $componentKey);
      if (!$value) {
        // missing a required key to compose the dsn - so give up
        // (note: defaults should be set in AppSettings::CONSTANTS and returned by get - so this is probably only password)
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
