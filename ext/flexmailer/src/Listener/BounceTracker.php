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
 * @service civi_flexmailer_bounce_tracker
 */
class BounceTracker extends AutoService {

  use IsActiveTrait;

  /**
   * Inject bounce-tracking codes.
   *
   * @param \Civi\FlexMailer\Event\ComposeBatchEvent $e
   */
  public function onCompose(ComposeBatchEvent $e) {
    if (!$this->isActive()) {
      return;
    }

    $mailing = $e->getMailing();
    $defaultReturnPath = \CRM_Core_BAO_MailSettings::defaultReturnPath();

    foreach ($e->getTasks() as $task) {
      /** @var \Civi\FlexMailer\FlexMailerTask $task */
      list($verp) = $mailing->getVerpAndUrlsAndHeaders(
        $e->getJob()->id, $task->getEventQueueId(), $task->getHash());

      if (!$task->getMailParam('Return-Path')) {
        $task->setMailParam('Return-Path', $defaultReturnPath ?? $verp['bounce']);
      }
      if (!$task->getMailParam('X-CiviMail-Bounce')) {
        $task->setMailParam('X-CiviMail-Bounce', $verp['bounce']);
      }
    }
  }

}
