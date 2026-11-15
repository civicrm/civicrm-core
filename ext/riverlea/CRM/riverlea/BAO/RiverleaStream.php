<?php

use Civi\Core\Attribute\CiviEventListener;
use CRM_riverlea_ExtensionUtil as E;
use Civi\Core\Event\PreEvent;
use Civi\Core\Event\PostEvent;

class CRM_riverlea_BAO_RiverleaStream extends CRM_riverlea_DAO_RiverleaStream {

  #[CiviEventListener(event: 'hook_civicrm_pre::{self}')]
  public static function self_hook_civicrm_pre(PreEvent $event): void {
    // munge label for default name
    if ($event->action === 'create' && !$event->params['name']) {
      $event->params['name'] = \CRM_Utils_String::munge($event->params['label']);
    }
  }

  #[CiviEventListener(event: 'hook_civicrm_post::{self}')]
  public static function self_hook_civicrm_post(PostEvent $event): void {
    \Civi::service('riverlea.style_loader')->clear();
  }

}
