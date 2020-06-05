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
 * Upgrade logic for FiveTwentySeven */
class CRM_Upgrade_Incremental_php_FiveTwentySeven extends CRM_Upgrade_Incremental_Base {

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

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_27_alpha1($rev) {
    // Add column before running sql which populates the column's values
    $this->addTask('Add serialize column to civicrm_custom_field', 'addColumn',
      'civicrm_custom_field', 'serialize', "int unsigned DEFAULT NULL COMMENT 'Serialization method - a non-null value indicates a multi-valued field.'"
    );
    $this->addTask('Make the label field required on price field value', 'priceFieldValueLabelRequired');
    $this->addTask('Make the name field required on civicrm_membership_type', 'nameMembershipTypeRequired');
    $this->addTask('Rebuild Multilingal Schema', 'rebuildMultilingalSchema', '5.27.alpha1');
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Install event cart extension', 'installEventCart');
  }

  /**
   * Install eventCart extension.
   *
   * This feature is restructured as a core extension - which is primarily a code cleanup step.
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function installEventCart(CRM_Queue_TaskContext $ctx) {
    // Install via direct SQL manipulation. Note that:
    // (1) This extension has no activation logic.
    // (2) On new installs, the extension is activated purely via default SQL INSERT.
    // (3) Caches are flushed at the end of the upgrade.
    // ($) Over long term, upgrade steps are more reliable in SQL. API/BAO sometimes don't work mid-upgrade.
    $insert = CRM_Utils_SQL_Insert::into('civicrm_extension')->row([
      'type' => 'module',
      'full_name' => 'eventcart',
      'name' => 'Event cart',
      'label' => 'Event cart',
      'file' => 'eventcart',
      'schema_version' => NULL,
      'is_active' => 1,
    ]);
    CRM_Core_DAO::executeQuery($insert->usingReplace()->toSQL());
    return TRUE;
  }

  public function priceFieldValueLabelRequired($ctx) {
    $domain = new CRM_Core_DAO_Domain();
    $domain->find(TRUE);
    if ($domain->locales) {
      $locales = explode(CRM_Core_DAO::VALUE_SEPARATOR, $domain->locales);
      foreach ($locales as $locale) {
        CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_price_field_value CHANGE `label_{$locale}` `label_{$locale}` varchar(255) NOT NULL   COMMENT 'Price field option label'", [], TRUE, NULL, FALSE, FALSE);
      }
    }
    else {
      CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_price_field_value CHANGE `label` `label` varchar(255) NOT NULL   COMMENT 'Price field option label'", [], TRUE, NULL, FALSE, FALSE);
    }
    return TRUE;
  }

  public function nameMembershipTypeRequired($ctx) {
    $domain = new CRM_Core_DAO_Domain();
    $domain->find(TRUE);
    if ($domain->locales) {
      $locales = explode(CRM_Core_DAO::VALUE_SEPARATOR, $domain->locales);
      foreach ($locales as $locale) {
        CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_membership_type CHANGE `name_{$locale}` `name_{$locale}` varchar(128) NOT NULL   COMMENT 'Name of Membership Type'", [], TRUE, NULL, FALSE, FALSE);
      }
    }
    else {
      CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_membership_type CHANGE `name` `name` varchar(128) NOT NULL   COMMENT 'Name of Membership Type'", [], TRUE, NULL, FALSE, FALSE);
    }
    return TRUE;
  }

}
