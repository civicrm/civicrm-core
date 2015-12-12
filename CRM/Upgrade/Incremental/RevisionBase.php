<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 * In Civi <= v4.6, each major release had an upgrade class, and each
 * point release had a function (`upgrade_12_34()`) and a file
 * (`Incremental/sql/12_34.mysql.tpl`).
 *
 * In Civi >= v4.7, this was flattened out so that upgrades are handled by
 * named scripts (with no particular meaning to major/minor versions).
 * This design reduces maintenance arising from code-review/forks/merges.
 *
 * The `RevisionBase` class provides an adapter: old revision-based upgrade
 * classes look like named migration scripts.
 *
 * @deprecated
 */
abstract class CRM_Upgrade_Incremental_RevisionBase extends CRM_Upgrade_Incremental_Base {

  /**
   * Get a list of incremental revisions.
   *
   * @return array
   */
  public abstract function getRevisions();

  /**
   * Verify DB state.
   *
   * @param $errors
   *
   * @return bool
   */
  public function verifyPreDBstate(&$errors) {
    return TRUE;
  }

  /**
   * Compute any messages which should be displayed before upgrade.
   *
   * Note: This function is called iteratively for each upcoming
   * revision to the database.
   *
   * @param $preUpgradeMessage
   * @param string $rev
   *   a version number, e.g. '4.8.alpha1', '4.8.beta3', '4.8.0'.
   * @param null $currentVer
   */
  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
  }

  /**
   * Compute any messages which should be displayed after upgrade.
   *
   * @param string $postUpgradeMessage
   *   alterable.
   * @param string $rev
   *   an intermediate version; note that setPostUpgradeMessage is called repeatedly with different $revs.
   */
  public function setPostUpgradeMessage(&$postUpgradeMessage, $rev) {
  }

  // --------------------------------------------

  /**
   * Adapt from new-style `createPreUpgradeMessage()` to old-style
   * `setPreUpgradeMessage(&$message)`.
   *
   * @param string $currentVer
   * @param string $endVer
   * @return string
   */
  public function createPreUpgradeMessage($currentVer, $endVer) {
    $preUpgradeMessage = '';
    foreach ($this->getRevisions() as $rev) {
      if (version_compare($currentVer, $rev) < 0) {
        $this->setPreUpgradeMessage($preUpgradeMessage, $rev, $currentVer);
      }
    }
    return $preUpgradeMessage;
  }

  /**
   * Enqueue upgrade tasks for each revision.
   *
   * @param \CRM_Queue_Queue $queue
   * @param $postUpgradeMessageFile
   * @param $startVer
   * @param $endVer
   */
  public function buildQueue(CRM_Queue_Queue $queue, $postUpgradeMessageFile, $startVer, $endVer) {
    foreach ($this->getRevisions() as $rev) {
      // proceed only if $currentVer < $rev
      if (version_compare($startVer, $rev) < 0) {
        $beginTask = new CRM_Queue_Task(
        // callback
          array('CRM_Upgrade_Incremental_RevisionBase', 'doIncrementalUpgradeStart'),
          // arguments
          array($rev),
          "Begin Upgrade to $rev"
        );
        $queue->createItem($beginTask);

        $task = new CRM_Queue_Task(
        // callback
          array('CRM_Upgrade_Incremental_RevisionBase', 'doIncrementalUpgradeStep'),
          // arguments
          array($rev, $startVer, $endVer, $postUpgradeMessageFile),
          "Upgrade DB to $rev"
        );
        $queue->createItem($task);

        $task = new CRM_Queue_Task(
        // callback
          array('CRM_Upgrade_Incremental_RevisionBase', 'doIncrementalUpgradeFinish'),
          // arguments
          array($rev, $startVer, $endVer, $postUpgradeMessageFile),
          "Finish Upgrade DB to $rev"
        );
        $queue->createItem($task);
      }
    }
  }

  /**
   * Perform an incremental version update.
   *
   * @param CRM_Queue_TaskContext $ctx
   * @param string $rev
   *   the target (intermediate) revision e.g '3.2.alpha1'.
   *
   * @return bool
   */
  public static function doIncrementalUpgradeStart(CRM_Queue_TaskContext $ctx, $rev) {
    $upgrade = new CRM_Upgrade_Form();

    // as soon as we start doing anything we append ".upgrade" to version.
    // this also helps detect any partial upgrade issues
    $upgrade->setVersion($rev . '.upgrade');

    return TRUE;
  }

  /**
   * Perform an incremental version update.
   *
   * @param CRM_Queue_TaskContext $ctx
   * @param string $rev
   *   the target (intermediate) revision e.g '3.2.alpha1'.
   * @param string $originalVer
   *   the original revision.
   * @param string $latestVer
   *   the target (final) revision.
   * @param string $postUpgradeMessageFile
   *   path of a modifiable file which lists the post-upgrade messages.
   *
   * @return bool
   */
  public static function doIncrementalUpgradeStep(CRM_Queue_TaskContext $ctx, $rev, $originalVer, $latestVer, $postUpgradeMessageFile) {
    $upgrade = new CRM_Upgrade_Form();

    $phpFunctionName = 'upgrade_' . str_replace('.', '_', $rev);

    $versionObject = $upgrade->incrementalPhpObject($rev);

    // pre-db check for major release.
    if ($upgrade->checkVersionRelease($rev, 'alpha1')) {
      if (!(is_callable(array($versionObject, 'verifyPreDBstate')))) {
        CRM_Core_Error::fatal("verifyPreDBstate method was not found for $rev");
      }

      $error = NULL;
      if (!($versionObject->verifyPreDBstate($error))) {
        if (!isset($error)) {
          $error = "post-condition failed for current upgrade for $rev";
        }
        CRM_Core_Error::fatal($error);
      }

    }

    $upgrade->setSchemaStructureTables($rev);

    if (is_callable(array($versionObject, $phpFunctionName))) {
      $versionObject->$phpFunctionName($rev, $originalVer, $latestVer);
    }
    else {
      $upgrade->processSQL($rev);
    }

    // set post-upgrade-message if any
    if (is_callable(array($versionObject, 'setPostUpgradeMessage'))) {
      $postUpgradeMessage = file_get_contents($postUpgradeMessageFile);
      $versionObject->setPostUpgradeMessage($postUpgradeMessage, $rev);
      file_put_contents($postUpgradeMessageFile, $postUpgradeMessage);
    }

    return TRUE;
  }

  /**
   * Perform an incremental version update.
   *
   * @param CRM_Queue_TaskContext $ctx
   * @param string $rev
   *   the target (intermediate) revision e.g '3.2.alpha1'.
   * @param string $currentVer
   *   the original revision.
   * @param string $latestVer
   *   the target (final) revision.
   * @param string $postUpgradeMessageFile
   *   path of a modifiable file which lists the post-upgrade messages.
   *
   * @return bool
   */
  public static function doIncrementalUpgradeFinish(CRM_Queue_TaskContext $ctx, $rev, $currentVer, $latestVer, $postUpgradeMessageFile) {
    $upgrade = new CRM_Upgrade_Form();
    $upgrade->setVersion($rev);
    CRM_Utils_System::flushCache();

    $config = CRM_Core_Config::singleton();
    $config->userSystem->flush();
    return TRUE;
  }

}
