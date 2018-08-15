<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * Upgrade logic for FiveFour */
class CRM_Upgrade_Incremental_php_FiveFour extends CRM_Upgrade_Incremental_Base {

  /**
   * Compute any messages which should be displayed beforeupgrade.
   *
   * Note: This function is called iteratively for each upcoming
   * revision to the database.
   *
   * @param string $preUpgradeMessage
   * @param string $rev
   *   a version number, e.g. '4.4.alpha1', '4.4.beta3', '4.4.0'.
   * @param null $currentVer
   */
  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
    // Example: Generate a pre-upgrade message.
    // if ($rev == '5.12.34') {
    //   $preUpgradeMessage .= '<p>' . ts('A new permission, "%1", has been added. This permission is now used to control access to the Manage Tags screen.', array(1 => ts('manage tags'))) . '</p>';
    // }
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
    if ($rev == '5.4.alpha1') {
      $postUpgradeMessage .= '<p>' . ts('A new permission, "%1", has been added. It is not granted by default. If your users create reports, you may wish to review their permissions.', array(1 => ts('save Report Criteria'))) . '</p>';
    }
    // Example: Generate a post-upgrade message.
    // if ($rev == '5.12.34') {
    //   $postUpgradeMessage .= '<br /><br />' . ts("By default, CiviCRM now disables the ability to import directly from SQL. To use this feature, you must explicitly grant permission 'import SQL datasource'.");
    // }
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_4_alpha1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
    $this->addTask('Add Cancel Button Setting to the Profile', 'addColumn',
      'civicrm_uf_group', 'add_cancel_button', "tinyint DEFAULT '1' COMMENT 'Should a Cancel button be included in this Profile form.'");
    $this->addTask('Add location_id if missing to group_contact table (affects some older installs CRM-20711)', 'addColumn',
      'civicrm_group_contact', 'location_id', "int(10) unsigned DEFAULT NULL COMMENT 'Optional location to associate with this membership'");
    $this->addTask('dev/core#107 - Add Activity\'s default assignee options', 'addActivityDefaultAssigneeOptions');
  }

  /**
   * This task adds the default assignee option values that can be selected when
   * creating or editing a new workflow's activity.
   *
   * @return bool
   */
  public static function addActivityDefaultAssigneeOptions() {
    // Add option group for activity default assignees:
    CRM_Core_BAO_OptionGroup::ensureOptionGroupExists(array(
      'name' => 'activity_default_assignee',
      'title' => ts('Activity default assignee'),
      'is_reserved' => 1,
    ));

    // Add option values for activity default assignees:
    $options = array(
      array('name' => 'NONE', 'label' => ts('None'), 'is_default' => 1),
      array('name' => 'BY_RELATIONSHIP', 'label' => ts('By relationship to case client')),
      array('name' => 'SPECIFIC_CONTACT', 'label' => ts('Specific contact')),
      array('name' => 'USER_CREATING_THE_CASE', 'label' => ts('User creating the case')),
    );

    foreach ($options as $option) {
      CRM_Core_BAO_OptionValue::ensureOptionValueExists(array(
        'option_group_id' => 'activity_default_assignee',
        'name' => $option['name'],
        'label' => $option['label'],
        'is_default' => CRM_Utils_Array::value('is_default', $option, 0),
        'is_active' => TRUE,
      ));
    }

    return TRUE;
  }

  /*
   * Important! All upgrade functions MUST add a 'runSql' task.
   * Uncomment and use the following template for a new upgrade version
   * (change the x in the function name):
   */

  //  /**
  //   * Upgrade function.
  //   *
  //   * @param string $rev
  //   */
  //  public function upgrade_5_0_x($rev) {
  //    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
  //    $this->addTask('Do the foo change', 'taskFoo', ...);
  //    // Additional tasks here...
  //    // Note: do not use ts() in the addTask description because it adds unnecessary strings to transifex.
  //    // The above is an exception because 'Upgrade DB to %1: SQL' is generic & reusable.
  //  }

  // public static function taskFoo(CRM_Queue_TaskContext $ctx, ...) {
  //   return TRUE;
  // }

}
