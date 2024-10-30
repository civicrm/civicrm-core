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

namespace Civi\WorkflowMessage;

use Civi\Api4\MessageTemplate;
use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * If someone sends an automated message for a test record (e.g. Contribution with `is_test=1`),
 * then we add a banner to the automated message.
 *
 * @service
 * @internal
 */
class TestBanner extends AutoService implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return [
      '&hook_civicrm_alterMailContent' => ['onAlterMailContent', -1000],
    ];
  }

  public function onAlterMailContent(array &$mailContent): void {
    // Only alter workflow-messages -- not CiviMail messages
    if (!empty($mailContent['mailingID'])) {
      return;
    }

    // Only alter test messages
    if (empty($mailContent['isTest'])) {
      return;
    }

    $testText = MessageTemplate::get(FALSE)
      ->setSelect(['msg_subject', 'msg_text', 'msg_html'])
      ->addWhere('workflow_name', '=', 'test_preview')
      ->addWhere('is_default', '=', TRUE)
      ->execute()->first();

    $mailContent['subject'] = $testText['msg_subject'] . $mailContent['subject'];
    $mailContent['text'] = $testText['msg_text'] . $mailContent['text'];
    $mailContent['html'] = preg_replace('/<body(.*)$/im', "<body\\1\n{$testText['msg_html']}", $mailContent['html']);
  }

}
