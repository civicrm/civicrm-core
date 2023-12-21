<?php

namespace Civi\Oembed\EntryPoint;

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

//TEMPLATE:START

/**
 * Begin processing of an embedded page-view on Drupal 8/9/10/etc.
 */
class Drupal8 {

  public static function main(): void {
    define('CIVICRM_OEMBED', 1);
    define('CIVICRM_UF_BASEURL', $GLOBALS['CIVICRM_OEMBED_META']['scriptUrl']); /* FIXME */

    // Do not accept cookies.
    // The whole issue is that browsers disagree on cookie-handling for embedded iframe content.
    // (Ex: Safari 16 doesn't send cookies; but Firefox 118 does.)
    // This means that `oembed.php` has the same cookie-less behavior for all browsers/users/tools.
    foreach (array_keys($_COOKIE) as $cookie) {
      unset($_COOKIE[$cookie]);
    }

    $autoloader = require_once 'autoload.php';
    $request = Request::createFromGlobals();
    $kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
    $kernel->boot();
    \Drupal::service('civicrm')->initialize();

    \Civi::service('oembed.router')->invoke([
      'route' => trim($_SERVER['PATH_INFO'], '/'),
      'drupalKernel' => $kernel,
      'drupalRequest' => $request,
    ]);
  }

}

//TEMPLATE:END
