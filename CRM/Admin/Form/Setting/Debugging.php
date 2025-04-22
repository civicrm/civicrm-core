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
 * This class generates form components for Error Handling and Debugging.
 */
class CRM_Admin_Form_Setting_Debugging extends CRM_Admin_Form_Setting {

  /**
   * Subset of settings on the page as defined using the legacy method.
   *
   * @var array
   *
   * @deprecated - do not add new settings here - the page to display
   * settings on should be defined in the setting metadata.
   */
  protected $_settings = [];

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->setTitle(ts('Settings - Debugging and Error Handling'));

    parent::buildQuickForm();

    $settingMetaData = $this->getSettingsMetaData();
    if (!CRM_Core_Config::singleton()->userSystem->supportsUfLogging()) {
      unset($settingMetaData['userFrameworkLogging']);
    }
    $this->assign('settings_fields', $settingMetaData);
  }

}
