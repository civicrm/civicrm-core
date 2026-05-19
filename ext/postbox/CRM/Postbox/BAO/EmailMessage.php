<?php

use Civi\Core\Event\PostEvent;
use Civi\Core\Event\PreEvent;
use Civi\Core\HookInterface;
use CRM_Postbox_ExtensionUtil as E;

class CRM_Postbox_BAO_EmailMessage extends CRM_Postbox_DAO_EmailMessage implements HookInterface {

  public static function self_hook_civicrm_pre(PreEvent $e): void {
    if ($e->action === 'create') {
      $e->params['created_id'] = \CRM_Core_Session::getLoggedInContactID();
    }
  }

  public static function self_hook_civicrm_post(PostEvent $e): void {
    if ($e->action !== 'create') {
      return;
    }

    $dispatcher = \Civi::service('civi.postbox.dispatcher');

    $dispatcher->queueNewMessage($e->id);
    $dispatcher->registerShutdownDispatcher();
  }

}
