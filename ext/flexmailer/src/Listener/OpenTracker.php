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

class OpenTracker extends BaseListener {

  /**
   * Inject open-tracking codes.
   *
   * @param \Civi\FlexMailer\Event\ComposeBatchEvent $e
   */
  public function onCompose(ComposeBatchEvent $e) {
    if (!$this->isActive() || !$e->getMailing()->open_tracking) {
      return;
    }

    $config = \CRM_Core_Config::singleton();

    // TODO: After v5.21 goes EOL, remove the $isLegacy check.
    $isLegacy = version_compare(\CRM_Utils_System::version(), '5.23.alpha', '<');

    foreach ($e->getTasks() as $task) {
      /** @var \Civi\FlexMailer\FlexMailerTask $task */
      $mailParams = $task->getMailParams();

      if (!empty($mailParams) && !empty($mailParams['html'])) {
        $openUrl = $isLegacy
          ? $config->userFrameworkResourceURL . "extern/open.php?q=" . $task->getEventQueueId()
          : \CRM_Utils_System::externUrl('extern/open', "q=" . $task->getEventQueueId());

        $mailParams['html'] .= "\n" . '<img src="' . htmlentities($openUrl) . "\" width='1' height='1' alt='' border='0'>";

        $task->setMailParams($mailParams);
      }
    }
  }

}
