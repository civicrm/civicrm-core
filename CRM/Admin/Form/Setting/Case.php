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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class generates form components for CiviCase.
 */
class CRM_Admin_Form_Setting_Case extends CRM_Admin_Form_Setting {

  protected $_settings = [
    'civicaseRedactActivityEmail' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'civicaseAllowMultipleClients' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'civicaseNaturalActivityTypeSort' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'civicaseActivityRevisions' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'civicaseShowCaseActivities' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
  ];

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Settings - CiviCase'));
    parent::buildQuickForm();
  }

}
