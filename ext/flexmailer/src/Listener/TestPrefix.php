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
 * @service civi_flexmailer_test_prefix
 */
class TestPrefix extends AutoService {

  use IsActiveTrait;

  /**
   * For any test mailings, the "Subject:" should have a prefix.
   *
   * @param \Civi\FlexMailer\Event\ComposeBatchEvent $e
   */
  public function onCompose(ComposeBatchEvent $e) {
    if (!$this->isActive() || !$e->getJob()->is_test) {
      return;
    }

    foreach ($e->getTasks() as $task) {
      /** @var \Civi\FlexMailer\FlexMailerTask $task */
      $subject = $task->getMailParam('Subject');
      $subject = ts('[CiviMail Draft]') . ' ' . $subject;
      $task->setMailParam('Subject', $subject);
    }
  }

}
