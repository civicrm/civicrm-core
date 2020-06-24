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

  protected $_settings = [
    'debug_enabled' => CRM_Core_BAO_Setting::DEVELOPER_PREFERENCES_NAME,
    'backtrace' => CRM_Core_BAO_Setting::DEVELOPER_PREFERENCES_NAME,
    'fatalErrorHandler' => CRM_Core_BAO_Setting::DEVELOPER_PREFERENCES_NAME,
    'assetCache' => CRM_Core_BAO_Setting::DEVELOPER_PREFERENCES_NAME,
    'environment' => CRM_Core_BAO_Setting::DEVELOPER_PREFERENCES_NAME,
  ];

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts(' Settings - Debugging and Error Handling '));
    if (CRM_Core_Config::singleton()->userSystem->supports_UF_Logging == '1') {
      $this->_settings['userFrameworkLogging'] = CRM_Core_BAO_Setting::DEVELOPER_PREFERENCES_NAME;
    }

    parent::buildQuickForm();
    if (Civi::settings()->getMandatory('environment') !== NULL) {
      $element = $this->getElement('environment');
      $element->freeze();
      CRM_Core_Session::setStatus(ts('The environment settings have been disabled because it has been overridden in the settings file.'), ts('Environment settings'), 'info');
    }
  }

}
