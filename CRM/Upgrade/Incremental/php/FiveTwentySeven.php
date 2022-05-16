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
 * Upgrade logic for FiveTwentySeven
 */
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
    if ($rev == '5.27.alpha1') {
      $preUpgradeMessage .= '<p>' . ts('Starting with version 5.28.0, CiviCRM will require the PHP Internationalization extension (PHP-Intl). In preparation for this, the system check will show a warning beginning in 5.27.0 if your site lacks this extension.') . '</p>';
    }
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
  }

  public static function priceFieldValueLabelRequired($ctx) {
    $locales = CRM_Core_I18n::getMultilingual();
    if ($locales) {
      foreach ($locales as $locale) {
        CRM_Core_DAO::executeQuery("UPDATE civicrm_price_field_value SET label_{$locale} = '' WHERE label_{$locale} IS NULL", [], TRUE, NULL, FALSE, FALSE);
        CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_price_field_value CHANGE `label_{$locale}` `label_{$locale}` varchar(255) NOT NULL   COMMENT 'Price field option label'", [], TRUE, NULL, FALSE, FALSE);
      }
    }
    else {
      CRM_Core_DAO::executeQuery("UPDATE civicrm_price_field_value SET label = '' WHERE label IS NULL");
      CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_price_field_value CHANGE `label` `label` varchar(255) NOT NULL   COMMENT 'Price field option label'", [], TRUE, NULL, FALSE, FALSE);
    }
    return TRUE;
  }

  public static function nameMembershipTypeRequired($ctx) {
    $locales = CRM_Core_I18n::getMultilingual();
    if ($locales) {
      foreach ($locales as $locale) {
        CRM_Core_DAO::executeQuery("UPDATE civicrm_membership_type SET name_{$locale} = '' WHERE name_{$locale} IS NULL", [], TRUE, NULL, FALSE, FALSE);
        CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_membership_type CHANGE `name_{$locale}` `name_{$locale}` varchar(128) NOT NULL   COMMENT 'Name of Membership Type'", [], TRUE, NULL, FALSE, FALSE);
      }
    }
    else {
      CRM_Core_DAO::executeQuery("UPDATE civicrm_membership_type SET name = '' WHERE name IS NULL");
      CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_membership_type CHANGE `name` `name` varchar(128) NOT NULL   COMMENT 'Name of Membership Type'", [], TRUE, NULL, FALSE, FALSE);
    }
    return TRUE;
  }

}
