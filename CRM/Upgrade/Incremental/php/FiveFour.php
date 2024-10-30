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
 * Upgrade logic for FiveFour
 */
class CRM_Upgrade_Incremental_php_FiveFour extends CRM_Upgrade_Incremental_Base {

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
      $postUpgradeMessage .= '<p>' . ts('A new permission, "%1", has been added. It is not granted by default. If your users create reports, you may wish to review their permissions.', [1 => ts('save Report Criteria')]) . '</p>';
    }
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_4_alpha1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
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
    CRM_Core_BAO_OptionGroup::ensureOptionGroupExists([
      'name' => 'activity_default_assignee',
      'title' => ts('Activity default assignee'),
      'is_reserved' => 1,
    ]);

    // Add option values for activity default assignees:
    $options = [
      ['name' => 'NONE', 'label' => ts('None'), 'is_default' => 1],
      ['name' => 'BY_RELATIONSHIP', 'label' => ts('By relationship to case client')],
      ['name' => 'SPECIFIC_CONTACT', 'label' => ts('Specific contact')],
      ['name' => 'USER_CREATING_THE_CASE', 'label' => ts('User creating the case')],
    ];

    foreach ($options as $option) {
      CRM_Core_BAO_OptionValue::ensureOptionValueExists([
        'option_group_id' => 'activity_default_assignee',
        'name' => $option['name'],
        'label' => $option['label'],
        'is_default' => $option['is_default'] ?? 0,
        'is_active' => TRUE,
      ]);
    }

    return TRUE;
  }

}
