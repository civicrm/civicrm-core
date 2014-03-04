<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Upgrade_Incremental_php_FourFive {
  const BATCH_SIZE = 5000;

  function verifyPreDBstate(&$errors) {
    return TRUE;
  }

  /**
   * Compute any messages which should be displayed beforeupgrade
   *
   * Note: This function is called iteratively for each upcoming
   * revision to the database.
   *
   * @param $postUpgradeMessage string, alterable
   * @param $rev string, a version number, e.g. '4.4.alpha1', '4.4.beta3', '4.4.0'
   * @return void
   */
  function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
  }

  /**
   * Compute any messages which should be displayed after upgrade
   *
   * @param $postUpgradeMessage string, alterable
   * @param $rev string, an intermediate version; note that setPostUpgradeMessage is called repeatedly with different $revs
   * @return void
   */
  function setPostUpgradeMessage(&$postUpgradeMessage, $rev) {
    if ($rev == '4.5.alpha1') {
      $postUpgradeMessage .= '<br /><br />' . ts('Default versions of the following System Workflow Message Templates have been modified to handle new functionality: <ul><li>Contributions - Receipt (off-line)</li><li>Contributions - Receipt (on-line)</li><li>Memberships - Receipt (on-line)</li><li>Pledges - Acknowledgement</li></ul> If you have modified these templates, please review the new default versions and implement updates as needed to your copies (Administer > Communications > Message Templates > System Workflow Messages).');
      $postUpgradeMessage .= '<br /><br />' . ts('This release allows you to view and edit multiple-record custom field sets in a table format which will be more usable in some cases. You can try out the format by navigating to Administer > Custom Data & Screens > Custom Fields. Click Settings for a custom field set and change Display Style to "Tab with Tables".');
    }
  }

  function upgrade_4_5_alpha1($rev) {
    // task to process sql
    $this->addTask(ts('Upgrade DB to 4.5.alpha1: SQL'), 'task_4_5_x_runSql', $rev);

    $this->addTask(ts('Set default for Individual name fields configuration'), 'addNameFieldOptions');

    return TRUE;
  }

  /**
   * Add defaults for the newly introduced name fields configuration in 'contact_edit_options' setting
   *
   * @return bool TRUE for success
   */
  static function addNameFieldOptions(CRM_Queue_TaskContext $ctx) {
    $query = "SELECT `value` FROM `civicrm_setting` WHERE `group_name` = 'CiviCRM Preferences' AND `name` = 'contact_edit_options'";
    $dao = CRM_Core_DAO::executeQuery($query);
    $dao->fetch();
    $oldValue = unserialize($dao->value);

    $newValue = $oldValue . '1214151617';

    $query = "UPDATE `civicrm_setting` SET `value` = %1 WHERE `group_name` = 'CiviCRM Preferences' AND `name` = 'contact_edit_options'";
    $params = array(1 => array(serialize($newValue), 'String'));
    CRM_Core_DAO::executeQuery($query, $params);

    return TRUE;
  }

  /**
   * (Queue Task Callback)
   */
  static function task_4_5_x_runSql(CRM_Queue_TaskContext $ctx, $rev) {
    $upgrade = new CRM_Upgrade_Form();
    $upgrade->processSQL($rev);

    return TRUE;
  }

  /**
   * Syntatic sugar for adding a task which (a) is in this class and (b) has
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
}
