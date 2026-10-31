<?php

namespace Civi\Search;

use Civi\Core\Service\AutoService;
use Civi\Core\Attribute\CiviEventListener;

/**
 * @service
 * @internal
 */
class AngularDependencyInjector extends AutoService {

  /**
   * Generate dynamic list of dependencies for the `crmSearchDisplay` module.
   *
   * It must require all the modules that provide a viewable search display type.
   */
  #[CiviEventListener(event: 'hook_civicrm_angularModules', priority: -999)]
  public function onAngularModules($e) {
    if (!isset($e->angularModules['crmSearchDisplay'])) {
      // This shouldn't happen. Condition probably isn't needed.
      return;
    }
    foreach (\Civi\Search\Display::getDisplayTypes(['id', 'name'], TRUE) as $displayType) {
      foreach ($e->angularModules as $name => $module) {
        if (isset($module['exports'][$displayType['name']])) {
          $requires[] = $name;
        }
      }
    }
    if (isset($requires)) {
      $e->angularModules['crmSearchDisplay']['requires'] = array_unique(array_merge($e->angularModules['crmSearchDisplay']['requires'], $requires));
    }
  }

}
