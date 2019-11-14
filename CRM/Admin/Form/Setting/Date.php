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
 * This class generates form components for Date Formatting.
 */
class CRM_Admin_Form_Setting_Date extends CRM_Admin_Form_Setting {

  public $_settings = [
    'dateformatDatetime' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
    'dateformatFull' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
    'dateformatPartial' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
    'dateformatYear' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
    'dateformatTime' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
    'dateformatFinancialBatch' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
    'dateformatshortdate' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
    'weekBegins' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
    'dateInputFormat' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
    'timeInputFormat' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
    'fiscalYearStart' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
  ];

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Settings - Date'));

    parent::buildQuickForm();
  }

}
