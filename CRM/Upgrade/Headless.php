<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Perform an upgrade without using the web-frontend
 */
class CRM_Upgrade_Headless {

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
    // lets get around the time limit issue if possible for upgrades
    if (!ini_get('safe_mode')) {
      set_time_limit(0);
    }

    $upgrade = new CRM_Upgrade_Form();
    list($currentVer, $latestVer) = $upgrade->getUpgradeVersions();

    if ($error = $upgrade->checkUpgradeableVersion($currentVer, $latestVer)) {
      throw new Exception($error);
    }

    // Disable our SQL triggers
    CRM_Core_DAO::dropTriggers();

    // CRM-11156
    $preUpgradeMessage = NULL;
    $upgrade->setPreUpgradeMessage($preUpgradeMessage, $currentVer, $latestVer);

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

}
