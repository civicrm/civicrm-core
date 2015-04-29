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
 | Version 3, 19 November 2007.                                       |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact CiviCRM LLC       |
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
 */
class CRM_Upgrade_Incremental_php_FourSeven {
  const BATCH_SIZE = 5000;

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
   *   a version number, e.g. '4.4.alpha1', '4.4.beta3', '4.4.0'.
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
   * @return void
   */
  public function setPostUpgradeMessage(&$postUpgradeMessage, $rev) {
  }


  /**
   * (Queue Task Callback)
   */
  public static function task_4_7_x_runSql(CRM_Queue_TaskContext $ctx, $rev) {
    $upgrade = new CRM_Upgrade_Form();
    $upgrade->processSQL($rev);

    return TRUE;
  }

  /**
   * Syntactic sugar for adding a task which (a) is in this class and (b) has
   * a high priority.
   *
   * After passing the $funcName, you can also pass parameters that will go to
   * the function. Note that all params must be serializable.
   */
  protected function addTask($title, $funcName) {
    $queue = CRM_Queue_Service::singleton()->load(array(
      'type' => 'Sql',
      'name' => CRM_Upgrade_Form::QUEUE_NAME,
    ));

    $args = func_get_args();
    $title = array_shift($args);
    $funcName = array_shift($args);
    $task = new CRM_Queue_Task(
      array(get_class($this), $funcName),
      $args,
      $title
    );
    $queue->createItem($task, array('weight' => -1));
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_4_7_alpha1($rev) {
    // Task to process sql.
    $this->addTask(ts('Update wysiwyg editor settings.'), 'updateWysiwyg');
  }

  /**
   * CRM-16354
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function updateWysiwyg(CRM_Queue_TaskContext $ctx) {
    $editorID = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'editor_id');
    // Previously any value indicated one of 4 wysiwyg editors shipped in core, and no value indicated 'Textfield'
    // Now Textfield is 1, CKEditor is 2, and the rest have been dropped from core.
    $editorID = $editorID ? 2 : 1;
    CRM_Core_BAO_Setting::setItem($editorID, CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'editor_id');

    return TRUE;
  }

}
