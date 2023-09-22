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

use Civi\FlexMailer\Event\ComposeBatchEvent;

class Attachments extends BaseListener {

  /**
   * Add any attachments.
   *
   * @param \Civi\FlexMailer\Event\ComposeBatchEvent $e
   */
  public function onCompose(ComposeBatchEvent $e) {
    if (!$this->isActive()) {
      return;
    }

    foreach ($e->getTasks() as $task) {
      /** @var \Civi\FlexMailer\FlexMailerTask $task */
      $task->setMailParam('attachments', $e->getAttachments());
    }
  }

}
