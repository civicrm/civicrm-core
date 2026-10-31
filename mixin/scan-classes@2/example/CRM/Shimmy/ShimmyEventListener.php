<?php

use Civi\Core\Attribute\CiviEventListener;

/**
 * Example class demonstrating a standalone event listener using CiviEventListener.
 */
class CRM_Shimmy_ShimmyEventListener {

  #[CiviEventListener(event: 'hook_civicrm_shimmyFooBar')]
  public function onShimmyFooBar(\Civi\Core\Event\GenericHookEvent $e): void {
    $e->data[] = "hello {$e->for} (CRM_Shimmy_ShimmyEventListener)";
  }

}
