<?php

namespace Civi\PhpStorm;

use Civi\Api4\Entity;
use Civi\Api4\Route;
use Civi\Core\Service\AutoService;
use Civi\Test\Invasive;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @service civi.phpstorm.url
 */
class UrlGenerator extends AutoService implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return [
      'civi.phpstorm.flush' => 'generate',
    ];
  }

  public function generate() {
    $routes = Route::get(FALSE)
      ->addSelect('path', 'is_public', 'page_callback')
      ->addOrderBy('path')
      ->execute();

    $urls = [];
    foreach ($routes as $route) {
      // $callback = (is_array($route['page_callback'])) ? $route['page_callback'][0] : $route['page_callback'];
      // $suffix = (preg_match('/_Form_/', $callback ?? '')) ? '?reset=1' : '';
      $suffix = '';

      if (preg_match('/(ajax|ipn)/', $route['path'])) {
        // We should have real metadata for web-service routes, but we'll use this weak guess for now...
        $urls[] = "service://"  . $route['path'];
      }
      $urls[] = (empty($route['is_public']) ? 'backend' : 'frontend') . "://" . $route['path'] . $suffix;
      $urls[] = "current://"  . $route['path'] . $suffix;
      $urls[] = "default://"  . $route['path'] . $suffix;
    }
    foreach (\CRM_Extension_System::singleton()->getFullContainer()->getKeys() as $key) {
      $urls[] = "ext://$key/";
    }
    foreach (array_keys(Invasive::get([\Civi::paths(), 'variableFactory'])) as $pathVar) {
      $urls[] = "asset://[$pathVar]/";
    }
    $urls[] = 'assetBuilder://'; /* Currently don't have a feed listing buildable assets... */

    $builder = new PhpStormMetadata('urls', __CLASS__);
    $builder->registerArgumentsSet('routes', ...$routes->column('path'));
    $builder->registerArgumentsSet('urls', ...$urls);
    $builder->addExpectedArguments('\CRM_Utils_System::url()', 0, 'routes');
    $builder->addExpectedArguments('\Civi::url()', 0, 'urls');

    $builder->write();
  }

}
