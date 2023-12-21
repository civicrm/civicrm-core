<?php

namespace Civi\Oembed\EntryPoint;

//TEMPLATE:START

/**
 * Begin processing of an embedded page-view on Drupal 7.
 */
class Drupal {

  public static function main(): void {

    define('CIVICRM_OEMBED', 1);
    define('CIVICRM_UF_BASEURL', $GLOBALS['CIVICRM_OEMBED_META']['scriptUrl']); /* FIXME */
    define('DRUPAL_ROOT', getcwd());

    // Do not accept cookies.
    // The whole issue is that browsers disagree on cookie-handling for embedded iframe content.
    // (Ex: Safari 16 doesn't send cookies; but Firefox 118 does.)
    // This means that `oembed.php` has the same cookie-less behavior for all browsers/users/tools.
    foreach (array_keys($_COOKIE) as $cookie) {
      unset($_COOKIE[$cookie]);
    }

    require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
    \drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
    \civicrm_initialize();

    \Civi::service('oembed.router')->invoke([
      'route' => trim($_SERVER['PATH_INFO'], '/'),
    ]);
  }

}

//TEMPLATE:END
