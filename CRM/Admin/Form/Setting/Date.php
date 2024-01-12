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

  /**
   * Subset of settings on the page as defined using the legacy method.
   *
   * @var array
   *
   * @deprecated - do not add new settings here - the page to display
   * settings on should be defined in the setting metadata.
   */
  protected $_settings = [
    // @todo remove these, define any not yet defined in the setting metadata.
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
    $this->setTitle(ts('Settings - Date'));

    parent::buildQuickForm();
  }

}
