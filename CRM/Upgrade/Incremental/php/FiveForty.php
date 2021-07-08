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
 * Upgrade logic for FiveForty */
class CRM_Upgrade_Incremental_php_FiveForty extends CRM_Upgrade_Incremental_Base {

  /**
   * Compute any messages which should be displayed beforeupgrade.
   *
   * Note: This function is called iteratively for each incremental upgrade step.
   * There must be a concrete step (eg 'X.Y.Z.mysql.tpl' or 'upgrade_X_Y_Z()').
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
   * Note: This function is called iteratively for each incremental upgrade step.
   * There must be a concrete step (eg 'X.Y.Z.mysql.tpl' or 'upgrade_X_Y_Z()').
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

  public function upgrade_5_40_alpha1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Add option list for group_used_for', 'addGroupOptionList');
    $this->addTask('core-issue#2486  - Add product_id foreign key to civicrm_contribution_product', 'addContributionProductFK');
    $this->addTask('Add membership_num_terms column to civicrm_line_item', 'addColumn',
      'civicrm_line_item', 'membership_num_terms', "int unsigned DEFAULT NULL COMMENT 'Number of terms for this membership (only supported in Order->Payment flow). If the field is NULL it means unknown and it will be assumed to be 1 during payment.create if entity_table is civicrm_membership'"
    );
    $this->addTask('Enable new CKEditor 4 Extension', 'installCkeditor4Extension');
    $this->addTask('Update CKeditor label to indicate it is version 4', 'updateCkeditorOptionLabel');
  }

  /**
   * @param CRM_Queue_TaskContext $ctx
   * @return bool
   */
  public static function addGroupOptionList(CRM_Queue_TaskContext $ctx) {
    $optionGroupId = \CRM_Core_BAO_OptionGroup::ensureOptionGroupExists([
      'name' => 'note_used_for',
      'title' => ts('Note Used For'),
      'is_reserved' => 1,
      'is_active' => 1,
      'is_locked' => 1,
    ]);
    $values = [
      ['value' => 'civicrm_relationship', 'name' => 'Relationship', 'label' => ts('Relationships')],
      ['value' => 'civicrm_contact', 'name' => 'Contact', 'label' => ts('Contacts')],
      ['value' => 'civicrm_participant', 'name' => 'Participant', 'label' => ts('Participants')],
      ['value' => 'civicrm_contribution', 'name' => 'Contribution', 'label' => ts('Contributions')],
    ];
    foreach ($values as $value) {
      \CRM_Core_BAO_OptionValue::ensureOptionValueExists($value + ['option_group_id' => $optionGroupId]);
    }
    return TRUE;
  }

  /**
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function addContributionProductFK(CRM_Queue_TaskContext $ctx): bool {
    if (!self::checkFKExists('civicrm_contribution_product', 'FK_civicrm_contribution_product_product_id')) {
      // dev/core#2680 Clear out any rows with problematic product_ids from the civicrm_contribution_product table.
      CRM_Core_DAO::executeQuery("DELETE FROM civicrm_contribution_product WHERE product_id NOT IN (SELECT id FROM civicrm_product)");
      CRM_Core_DAO::executeQuery("
        ALTER TABLE `civicrm_contribution_product`
          ADD CONSTRAINT `FK_civicrm_contribution_product_product_id`
            FOREIGN KEY (`product_id`) REFERENCES `civicrm_product` (`id`)
            ON DELETE CASCADE;
      ", [], TRUE, NULL, FALSE, FALSE);
    }

    return TRUE;
  }

  /**
   * @param CRM_Queue_TaskContext $ctx
   * @return bool
   */
  public static function updateCkeditorOptionLabel(CRM_Queue_TaskContext $ctx) {
    civicrm_api3('OptionValue', 'get', [
      'name' => 'CKEditor',
      'option_group_id' => 'wysiwyg_editor',
      'api.OptionValue.create' => [
        'label' => ts('CKEditor 4'),
        'id' => "\$value.id",
      ],
    ]);
    return TRUE;
  }

  /**
   * Install CKEditor4 extension.
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   *
   * @throws \CRM_Core_Exception
   */
  public static function installCkeditor4Extension(CRM_Queue_TaskContext $ctx) {
    $insert = CRM_Utils_SQL_Insert::into('civicrm_extension')->row([
      'type' => 'module',
      'full_name' => 'ckeditor4',
      'name' => 'CKEditor4',
      'label' => 'CKEditor4',
      'file' => 'ckeditor4',
      'schema_version' => NULL,
      'is_active' => 1,
    ]);
    CRM_Core_DAO::executeQuery($insert->usingReplace()->toSQL());

    return TRUE;
  }

}
