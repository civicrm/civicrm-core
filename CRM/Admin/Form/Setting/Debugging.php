<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * @copyright CiviCRM LLC (c) 2004-2017
 */

/**
 * This class generates form components for Error Handling and Debugging.
 */
class CRM_Admin_Form_Setting_Debugging extends CRM_Admin_Form_Setting {

  protected $_settings = array(
    'logging_method' => CRM_Core_BAO_Setting::DEVELOPER_PREFERENCES_NAME,
    'debug_enabled' => CRM_Core_BAO_Setting::DEVELOPER_PREFERENCES_NAME,
    'backtrace' => CRM_Core_BAO_Setting::DEVELOPER_PREFERENCES_NAME,
    'fatalErrorHandler' => CRM_Core_BAO_Setting::DEVELOPER_PREFERENCES_NAME,
  );

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Settings - Debugging and Error Handling'));

    // Get expected location: CiviCRM error log.
    if (!isset(\Civi::$statics['CRM_Core_Error']['logger_file'])) {
      // Log file location is set only when first message is logged via CRM_Core_Error.
      CRM_Core_Error::debug_log_message('Finding CRM_Core_Error logfile.');
    }
    $this->assign('civicrm_logfile_location', \Civi::$statics['CRM_Core_Error']['logger_file']);

    // Get expected location/destination: PHP error log.
    $php_error_log = ini_get('error_log');
    if (empty($php_error_log)) {
      $php_error_log = 'error_log unset, will use SAPI error log';
    }
    $this->assign('php_error_log_location', $php_error_log);

    if (CRM_Core_Config::singleton()->userSystem->supports_UF_Logging == '1') {
      $this->_settings['userFrameworkLogging'] = CRM_Core_BAO_Setting::DEVELOPER_PREFERENCES_NAME;
    }

    Civi::log()->info('Hello, log!');
    Civi::log()->warning('Oh no, {kittens}!', array('kittens' => 'wildcats'));
    $var = 'the coffee';
    Civi::log()->error("There is something bad with {var}.", array(
        'var' => $var,
    ));

    parent::buildQuickForm();
  }

}
