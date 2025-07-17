<?php

use CRM_Riverlea_ExtensionUtil as E;
use Civi\Core\HookInterface;
use Civi\Core\Event\PreEvent;
use Civi\Core\Event\PostEvent;

class CRM_Riverlea_BAO_RiverleaStream extends CRM_Riverlea_DAO_RiverleaStream implements HookInterface {

  public static function self_hook_civicrm_pre(PreEvent $event): void {
    // munge label for default name
    if ($event->action === 'create' && !$event->params['name']) {
      $event->params['name'] = \CRM_Utils_String::munge($event->params['label']);
    }
  }

  public static function self_hook_civicrm_postCommit(PostEvent $event): void {
    \Civi::service('themes')->clear();
  }

}
