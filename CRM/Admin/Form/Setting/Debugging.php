<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * This class generates form components for Error Handling and Debugging.
 */
class CRM_Admin_Form_Setting_Debugging extends CRM_Admin_Form_Setting {

  protected $_settings = array(
    'debug_enabled' => CRM_Core_BAO_Setting::DEVELOPER_PREFERENCES_NAME,
    'backtrace' => CRM_Core_BAO_Setting::DEVELOPER_PREFERENCES_NAME,
    'fatalErrorHandler' => CRM_Core_BAO_Setting::DEVELOPER_PREFERENCES_NAME,
    'assetCache' => CRM_Core_BAO_Setting::DEVELOPER_PREFERENCES_NAME,
    'environment' => CRM_Core_BAO_Setting::DEVELOPER_PREFERENCES_NAME,
  );

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
