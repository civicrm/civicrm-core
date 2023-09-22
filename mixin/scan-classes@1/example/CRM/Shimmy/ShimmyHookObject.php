<?php

use Civi\Core\Service\AutoService;
use Civi\Core\HookInterface;

/**
 * In this example, we use a non-static hook function.
 *
 * @service shimmy.hook.object
 */
class CRM_Shimmy_ShimmyHookObject extends AutoService implements HookInterface {

  public function hook_civicrm_shimmyFooBar(array &$data, string $name): void {
    _shimmy_assert_service_object(static::class, 'shimmy.hook.object', static::class);
    $data[] = "hello $name (shimmy.hook.object, as hook)";
  }

  public function on_hook_civicrm_shimmyFooBar(\Civi\Core\Event\GenericHookEvent $e): void {
    _shimmy_assert_service_object(static::class, 'shimmy.hook.object', static::class);
    $e->data[] = "hello {$e->for} (shimmy.hook.object, as event)";
  }

}
