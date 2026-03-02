<?php

namespace Civi\Search;

use Civi\Core\Service\AutoSubscriber;

class AngularDependencyInjector extends AutoSubscriber {

  public static function getSubscribedEvents(): array {
    return [
      // Late listener, so that all modules are present before adding dependencies.
      'hook_civicrm_angularModules' => ['onAngularModules', -999],
    ];
  }

  /**
   * Generate dynamic list of dependencies for the `crmSearchDisplay` module.
   *
   * It must require all the modules that provide a viewable search display type.
   */
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
