<?php

namespace Civi\PhpStorm;

use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @service civi.phpstorm.permissions
 */
class PermissionsGenerator extends AutoService implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return ['civi.phpstorm.flush' => 'generate'];
  }

  public function generate() {
    $permissions = \Civi\Api4\Permission::get(FALSE)->execute()->column('name');
    $methods = ['check'];
    $builder = new PhpStormMetadata('permissions', __CLASS__);
    $builder->registerArgumentsSet('permissionNames', ...$permissions);
    foreach ($methods as $method) {
      $builder->addExpectedArguments('\CRM_Core_Permission::' . $method . '()', 0, 'permissionNames');
    }
    $builder->write();
  }

}
