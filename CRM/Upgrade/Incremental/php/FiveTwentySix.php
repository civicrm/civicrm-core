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
 * Upgrade logic for FiveTwentySix
 */
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
    if (!function_exists('civi_wp')) {
      //exit
    }
    elseif ($rev == '5.26.alpha1') {
      $preUpgradeMessage .= '<br/>' . ts("WARNING: CiviCRM 5.26 and later changes how front-end CiviCRM URLs are formed in WordPress.  Please <a href='%1' target='_blank'>read this blog post before upgrading</a> . You may need to update settings at your payment Processor for recurring payments. If you have an external service that sends callback messages to CiviCRM, you may need to update the settings at the external service to use the new URL format.", [
        1 => 'https://civicrm.org/blog/kcristiano/civicrm-526-and-wordpress-important-notice',
      ]);
    }
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
    if (!function_exists('civi_wp')) {
      //exit
    }
    elseif ($rev == '5.26.alpha1') {
      $postUpgradeMessage .= '<br/>' . ts("WARNING: CiviCRM 5.26 and later changes how front-end CiviCRM URLs are formed in WordPress.  Please <a href='%1' target='_blank'>read this blog post before upgrading</a> . You may need to update settings at your payment Processor for recurring payments. If you have an external service that sends callback messages to CiviCRM, you may need to update the settings at the external service to use the new URL format.", [
        1 => 'https://civicrm.org/blog/kcristiano/civicrm-526-and-wordpress-important-notice',
      ]);
    }
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_26_alpha1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Add option value for nl_BE', 'addNLBEOptionValue');
    $this->addTask('Add workflow_name to civicrm_msg_template', 'addColumn', 'civicrm_msg_template', 'workflow_name',
      "VARCHAR(255) DEFAULT NULL COMMENT 'Name of workflow'", FALSE, '5.26.0');
    $this->addTask('Populate workflow_name in civicrm_msg_template', 'populateWorkflowName');

    // Additional tasks here...
    // Note: do not use ts() in the addTask description because it adds unnecessary strings to transifex.
    // The above is an exception because 'Upgrade DB to %1: SQL' is generic & reusable.
  }

  /**
   * Update workflow_name based on workflow_id values.
   */
  public static function populateWorkflowName() {
    CRM_Core_DAO::executeQuery('UPDATE civicrm_msg_template
      LEFT JOIN  civicrm_option_value ov ON ov.id = workflow_id
      SET workflow_name = ov.name'
    );
    return TRUE;
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
