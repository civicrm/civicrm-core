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
 * @service civi_flexmailer_basic_headers
 */
class BasicHeaders extends AutoService {

  use IsActiveTrait;

  /**
   * Inject basic headers
   *
   * @param \Civi\FlexMailer\Event\ComposeBatchEvent $e
   */
  public function onCompose(ComposeBatchEvent $e) {
    if (!$this->isActive()) {
      return;
    }

    $mailing = $e->getMailing();

    foreach ($e->getTasks() as $task) {
      /** @var \Civi\FlexMailer\FlexMailerTask $task */

      if ($task->hasContent()) {
        continue;
      }

      list($verp) = $mailing->getVerpAndUrlsAndHeaders(
        $e->getJob()->id, $task->getEventQueueId(), $task->getHash());

      $mailParams = [];
      $mailParams['List-Unsubscribe'] = "<mailto:{$verp['unsubscribe']}>";
      \CRM_Mailing_BAO_Mailing::addMessageIdHeader($mailParams, 'm', NULL, $task->getEventQueueId(), $task->getHash());
      $mailParams['Precedence'] = 'bulk';
      $mailParams['job_id'] = $e->getJob()->id;

      $mailParams['From'] = "\"{$mailing->from_name}\" <{$mailing->from_email}>";

      // This old behavior for choosing Reply-To feels flawed to me -- if
      // the user has chosen a Reply-To that matches the From, then it uses VERP?!
      // $mailParams['Reply-To'] = $verp['reply'];
      // if ($mailing->replyto_email && ($mailParams['From'] != $mailing->replyto_email)) {
      //  $mailParams['Reply-To'] = $mailing->replyto_email;
      // }

      if (!$mailing->override_verp) {
        $mailParams['Reply-To'] = $verp['reply'];
      }
      elseif ($mailing->replyto_email && ($mailParams['From'] != $mailing->replyto_email)) {
        $mailParams['Reply-To'] = $mailing->replyto_email;
      }

      $task->setMailParams(array_merge(
        $mailParams,
        $task->getMailParams()
      ));
    }
  }

}
