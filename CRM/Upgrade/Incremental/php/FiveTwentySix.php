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
 * Upgrade logic for FiveTwentySix */
class CRM_Upgrade_Incremental_php_FiveTwentySix extends CRM_Upgrade_Incremental_Base {

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
    // Example: Generate a post-upgrade message.
    // if ($rev == '5.12.34') {
    //   $postUpgradeMessage .= '<br /><br />' . ts("By default, CiviCRM now disables the ability to import directly from SQL. To use this feature, you must explicitly grant permission 'import SQL datasource'.");
    // }
  }

  /*
   * Important! All upgrade functions MUST add a 'runSql' task.
   * Uncomment and use the following template for a new upgrade version
   * (change the x in the function name):
   */

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_26_alpha1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Add option value for nl_BE', 'addNLBEOptionValue');
    // Additional tasks here...
    // Note: do not use ts() in the addTask description because it adds unnecessary strings to transifex.
    // The above is an exception because 'Upgrade DB to %1: SQL' is generic & reusable.
  }

  /**
   * Add option value for nl_BE language.
   *
   * @param CRM_Queue_TaskContext $ctx
   */
  public static function addNLBEOptionValue(CRM_Queue_TaskContext $ctx) {
    CRM_Core_BAO_OptionValue::ensureOptionValueExists([
      'option_group_id' => 'languages',
      'name' => 'nl_BE',
      'label' => ts('Dutch (Belgium)'),
      'value' => 'nl',
      'is_active' => 1,
    ]);
    // Update the existing nl_NL entry.
    $sql = CRM_Utils_SQL::interpolate('UPDATE civicrm_option_value SET label = @newLabel WHERE option_group_id = #group AND name = @name AND label IN (@oldLabels)', [
      'name' => 'nl_NL',
      'newLabel' => ts('Dutch (Netherlands)'),
      // Adding check against old label in case they've customized it, in which
      // case we don't want to overwrite that. The ts() part is tricky since
      // it depends if they installed it in English first.
      'oldLabels' => ['Dutch', ts('Dutch')],
      'group' => CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_option_group WHERE name = "languages"'),
    ]);
    CRM_Core_DAO::executeQuery($sql);
    return TRUE;
  }

}
