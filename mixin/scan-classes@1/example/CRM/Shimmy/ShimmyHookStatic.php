<?php

use Civi\Core\Service\AutoService;
use Civi\Core\HookInterface;

/**
 * In this example, we use a static hook function.
 *
 * @service
 * @internal
 */
class CRM_Shimmy_ShimmyHookStatic extends AutoService implements HookInterface {

  public static function hook_civicrm_shimmyFooBar(array &$data, string $name): void {
    _shimmy_assert_service_object(static::class, static::class, 'crm.shimmy.shimmyHookStatic');
    $data[] = "hello $name (CRM_Shimmy_ShimmyHookStatic, as hook)";
  }

  public static function on_hook_civicrm_shimmyFooBar(\Civi\Core\Event\GenericHookEvent $e): void {
    _shimmy_assert_service_object(static::class, static::class, 'crm.shimmy.shimmyHookStatic');
    $e->data[] = "hello {$e->for} (CRM_Shimmy_ShimmyHookStatic, as event)";
  }

}
