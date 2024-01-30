<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */
namespace Civi\FlexMailer\Listener;

use Civi\Core\Service\AutoService;
use Civi\FlexMailer\Event\ComposeBatchEvent;

/**
 * @service civi_flexmailer_hooks
 */
class HookAdapter extends AutoService {

  use IsActiveTrait;

  /**
   * Expose to hook_civicrm_alterMailParams.
   *
   * @param \Civi\FlexMailer\Event\ComposeBatchEvent $e
   */
  public function onCompose(ComposeBatchEvent $e) {
    if (!$this->isActive()) {
      return;
    }

    foreach ($e->getTasks() as $task) {
      /** @var \Civi\FlexMailer\FlexMailerTask $task */
      $mailParams = $task->getMailParams();
      if ($mailParams) {
        \CRM_Utils_Hook::alterMailParams($mailParams, 'flexmailer');
        $task->setMailParams($mailParams);
      }
    }
  }

}
