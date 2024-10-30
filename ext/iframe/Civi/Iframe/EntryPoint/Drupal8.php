<?php

namespace Civi\Iframe\EntryPoint;

use Civi\Iframe\IframeDrupalKernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

//TEMPLATE:START

/**
 * Begin processing of an embedded page-view on Drupal 8/9/10/etc.
 */
class Drupal8 {

  public static function main(): void {
    define('CIVICRM_IFRAME', 1);

    // Do not accept cookies.
    // The whole issue is that browsers disagree on cookie-handling for embedded iframe content.
    // (Ex: Safari 16 doesn't send cookies; but Firefox 118 does.)
    // This means that `iframe.php` has the same cookie-less behavior for all browsers/users/tools.
    foreach (array_keys($_COOKIE) as $cookie) {
      unset($_COOKIE[$cookie]);
    }

    $GLOBALS['civicrm_url_defaults'][]['scheme'] = 'iframe';

    /** @var \Composer\Autoload\ClassLoader $autoloader */
    $autoloader = require_once 'autoload.php';
    $autoloader->addPsr4('Civi\\Iframe\\', realpath($GLOBALS['CIVICRM_IFRAME_META']['extPath']) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR);

    $route = trim($_SERVER['PATH_INFO'], '/');

    $request = Request::createFromGlobals();
    $kernel = IframeDrupalKernel::createFromRequest($request, $autoloader, 'prod');
    $kernel->boot();
    $kernel->preHandle($request);
    $request->attributes->set(\Drupal\Core\Routing\RouteObjectInterface::ROUTE_OBJECT, new \Symfony\Component\Routing\Route($route));
    $request->attributes->set(\Drupal\Core\Routing\RouteObjectInterface::ROUTE_NAME, 'civicrm.' . implode('_', explode('/', $route)));

    \Drupal::service('civicrm')->initialize();
    \Drupal::service('event_dispatcher')->addListener('kernel.response', function(ResponseEvent $event) {
      $event->getResponse()->headers->remove('X-Frame-Options');
    });

    \Civi::service('iframe.router')->invoke([
      'route' => $route,
      'drupalKernel' => $kernel,
      'drupalRequest' => $request,
    ]);
  }

}

//TEMPLATE:END
