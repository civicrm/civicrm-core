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

use Civi\FlexMailer\Event\RunEvent;

/**
 * Class Abdicator
 * @package Civi\FlexMailer\Listener
 *
 * FlexMailer is in incubation -- it's a heavily reorganized version
 * of the old MailingJob::deliver*() functions. It hasn't been tested as
 * thoroughly and may not have perfect parity.
 *
 * During incubation, we want to mostly step-aside -- for traditional
 * mailings, simply continue using the old system.
 */
class Abdicator {

  /**
   * @param \CRM_Mailing_BAO_Mailing $mailing
   * @return bool
   */
  public static function isFlexmailPreferred($mailing) {
    if ($mailing->sms_provider_id) {
      return FALSE;
    }

    // Use FlexMailer for new-style email blasts (with custom `template_type`).
    if ($mailing->template_type && $mailing->template_type !== 'traditional') {
      return TRUE;
    }
    return TRUE;
  }

  /**
   * Abdicate; defer to the old system during delivery.
   *
   * @param \Civi\FlexMailer\Event\RunEvent $e
   */
  public function onRun(RunEvent $e) {
    if (self::isFlexmailPreferred($e->getMailing())) {
      // OK, we'll continue running.
      return;
    }

    // Nope, we'll abdicate.
    $e->stopPropagation();
    $isDelivered = $e->getJob()->deliver(
      $e->context['deprecatedMessageMailer'],
      $e->context['deprecatedTestParams']
    );
    $e->setCompleted($isDelivered);
  }

  /**
   * Abdicate; defer to the old system when checking completeness.
   *
   * @param \Civi\FlexMailer\Event\CheckSendableEvent $e
   */
  public function onCheckSendable($e) {
    if (self::isFlexmailPreferred($e->getMailing())) {
      // OK, we'll continue running.
      return;
    }

    $e->stopPropagation();
    $errors = \CRM_Mailing_BAO_Mailing::checkSendable($e->getMailing());
    if (is_array($errors)) {
      foreach ($errors as $key => $message) {
        $e->setError($key, $message);;
      }
    }
  }

}
