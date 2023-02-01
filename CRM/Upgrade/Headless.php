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

/**
 * Perform an upgrade without using the web-frontend
 */
class CRM_Upgrade_Headless {

  /**
   * Pre Upgrade Message
   * @var string
   *   HTML-formatted message
   */
  private $preUpgradeMessage;

  /**
   * Perform an upgrade without using the web-frontend
   *
   * @param bool $enablePrint
   *
   * @throws Exception
   * @return array
   *   - with keys:
   *   - message: string, HTML-ish blob
   */
  public function run($enablePrint = TRUE) {
    set_time_limit(0);

    $upgrade = new CRM_Upgrade_Form();
    [$currentVer, $latestVer] = $upgrade->getUpgradeVersions();

    if ($error = $upgrade->checkUpgradeableVersion($currentVer, $latestVer)) {
      throw new Exception($error);
    }

    // Disable our SQL triggers
    CRM_Core_DAO::dropTriggers();

    // CRM-11156
    if (empty($this->preUpgradeMessage)) {
      $preUpgradeMessage = NULL;
      $upgrade->setPreUpgradeMessage($preUpgradeMessage, $currentVer, $latestVer);
      $this->preUpgradeMessage = $preUpgradeMessage;
    }

    $postUpgradeMessageFile = CRM_Utils_File::tempnam('civicrm-post-upgrade');
    $queueRunner = new CRM_Queue_Runner([
      'title' => ts('CiviCRM Upgrade Tasks'),
      'queue' => CRM_Upgrade_Form::buildQueue($currentVer, $latestVer, $postUpgradeMessageFile),
    ]);
    $queueResult = $queueRunner->runAll();
    if ($queueResult !== TRUE) {
      $errorMessage = CRM_Core_Error::formatTextException($queueResult['exception']);
      CRM_Core_Error::debug_log_message($errorMessage);
      if ($enablePrint) {
        print ($errorMessage);
      }
      // FIXME test
      throw $queueResult['exception'];
    }

    CRM_Upgrade_Form::doFinish();

    $message = file_get_contents($postUpgradeMessageFile);
    return [
      'latestVer' => $latestVer,
      'message' => $message,
      'text' => CRM_Utils_String::htmlToText($message),
    ];
  }

  /**
   * Get the pre-upgrade message.
   *
   * @return array
   *   The upgrade message, in HTML and text formats.
   *   Ex: ['message' => '<p>Foo</p><b>Bar</p>', 'text' => ["Foo\n\nBar"]]
   * @throws \Exception
   */
  public function getPreUpgradeMessage(): array {
    $upgrade = new CRM_Upgrade_Form();
    [$currentVer, $latestVer] = $upgrade->getUpgradeVersions();

    if ($error = $upgrade->checkUpgradeableVersion($currentVer, $latestVer)) {
      throw new Exception($error);
    }
    // CRM-11156
    if (empty($this->preUpgradeMessage)) {
      $preUpgradeMessage = NULL;
      $upgrade->setPreUpgradeMessage($preUpgradeMessage, $currentVer, $latestVer);
      $this->preUpgradeMessage = $preUpgradeMessage;
    }
    return [
      'message' => $this->preUpgradeMessage,
      'text' => CRM_Utils_String::htmlToText($this->preUpgradeMessage),
    ];
  }

}
