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
  protected $_settings = [
    // @todo remove these, define any not yet defined in the setting metadata.
    'debug_enabled' => CRM_Core_BAO_Setting::DEVELOPER_PREFERENCES_NAME,
    'backtrace' => CRM_Core_BAO_Setting::DEVELOPER_PREFERENCES_NAME,
    'fatalErrorHandler' => CRM_Core_BAO_Setting::DEVELOPER_PREFERENCES_NAME,
    'assetCache' => CRM_Core_BAO_Setting::DEVELOPER_PREFERENCES_NAME,
    'esm_loader' => CRM_Core_BAO_Setting::DEVELOPER_PREFERENCES_NAME,
    'environment' => CRM_Core_BAO_Setting::DEVELOPER_PREFERENCES_NAME,
  ];

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->setTitle(ts(' Settings - Debugging and Error Handling '));
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
