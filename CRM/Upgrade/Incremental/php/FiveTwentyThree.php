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
 * Upgrade logic for FiveTwentyThree
 */
class CRM_Upgrade_Incremental_php_FiveTwentyThree extends CRM_Upgrade_Incremental_Base {

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
    if ($rev == '5.23.alpha1' && version_compare($currentVer, '4.7', '>=')) {
      if ($this->hasConfigBackendData()) {
        $preUpgradeMessage .= '<br/>' . ts("WARNING: The column \"<code>civicrm_domain.config_backend</code>\" is <a href='%2'>flagged for removal</a>. However, the upgrader has detected data in this copy of \"<code>civicrm_domain.config_backend</code>\". Please <a href='%1' target='_blank'>report</a> anything you can about the usage of this column. In the mean-time, the data will be preserved.", [
          1 => 'https://civicrm.org/bug-reporting',
          2 => 'https://lab.civicrm.org/dev/core/issues/1387',
        ]);
      }
    }
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_23_alpha1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Remove Google + location option', 'removeGooglePlusOption');
    $this->addTask('dev/mailing#59 Add in IMAP_XOAUTH2 protocol option for mailbox access', 'addXoauth2ProtocolOption');
    $this->addTask('dev/translation#34 Fix contact-reference option for Postal Code', 'fixContactRefOptionPostalCode');

    // (dev/core#1387) This column was dropped in 4.7.alpha1, but it was still created on new installs.
    if (!$this->hasConfigBackendData()) {
      $this->addTask('Drop column "civicrm_domain.config_backend"', 'dropColumn', 'civicrm_domain', 'config_backend');
    }
  }

  /**
   * Add in the IMAP XOAUTH2 mailing protocol option
   */
  public static function addXoauth2ProtocolOption(CRM_Queue_TaskContext $ctx) {
    CRM_Core_BAO_OptionValue::ensureOptionValueExists([
      'option_group_id' => 'mail_protocol',
      'name' => 'IMAP_XOAUTH2',
      'label' => 'IMAP XOAUTH2',
      'is_active' => FALSE,
    ]);
    return TRUE;
  }

  /**
   * Remove Google + option value option for website type
   * only if there is no websites using it
   */
  public static function removeGooglePlusOption(CRM_Queue_TaskContext $ctx) {
    $googlePlusValue = CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Website', 'website_type_id', 'Google_');
    if ($googlePlusValue) {
      $values = CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_website WHERE website_type_id = %1", [1 => [$googlePlusValue, 'Positive']])->fetchAll();
      if (empty($values)) {
        $optionGroup = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_option_group WHERE name = 'website_type'");
        \Civi\Api4\OptionValue::delete()
          ->addWhere('value', '=', $googlePlusValue)
          ->addWhere('option_group_id', '=', $optionGroup)
          ->setCheckPermissions(FALSE)
          ->execute();
      }
    }
    return TRUE;
  }

  /**
   * Fix the Contact Reference 'Postal Code' option.
   */
  public static function fixContactRefOptionPostalCode(CRM_Queue_TaskContext $ctx) {
    $optionGroup = \Civi\Api4\OptionGroup::get()
      ->setSelect(['id'])
      ->addWhere('name', '=', 'contact_reference_options')
      ->setCheckPermissions(FALSE)
      ->execute()
      ->first();

    if (!$optionGroup) {
      return TRUE;
    }

    $optionValue = \Civi\Api4\OptionValue::get()
      ->setSelect(['id', 'name'])
      ->addWhere('option_group_id', '=', $optionGroup['id'])
      ->addWhere('label', '=', ts('Postal Code'))
      ->setCheckPermissions(FALSE)
      ->execute()
      ->first();

    if (!$optionValue || $optionValue['name'] == 'postal_code') {
      return TRUE;
    }

    \Civi\Api4\OptionValue::update()
      ->addWhere('id', '=', $optionValue['id'])
      ->addValue('name', 'postal_code')
      ->setCheckPermissions(FALSE)
      ->execute();

    return TRUE;
  }

  /**
   * @return bool
   */
  private function hasConfigBackendData() {
    return CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_domain', 'config_backend')
    && CRM_Core_DAO::singleValueQuery('SELECT count(*) c FROM `civicrm_domain` WHERE config_backend IS NOT NULL') > 0;
  }

}
