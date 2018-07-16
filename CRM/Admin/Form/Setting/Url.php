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
 * This class generates form components for Site Url.
 */
class CRM_Admin_Form_Setting_Url extends CRM_Admin_Form_Setting {
  protected $_settings = array(
    'disable_core_css' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'userFrameworkResourceURL' => CRM_Core_BAO_Setting::URL_PREFERENCES_NAME,
    'imageUploadURL' => CRM_Core_BAO_Setting::URL_PREFERENCES_NAME,
    'customCSSURL' => CRM_Core_BAO_Setting::URL_PREFERENCES_NAME,
    'extensionsURL' => CRM_Core_BAO_Setting::URL_PREFERENCES_NAME,
  );

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Settings - Resource URLs'));
    $settingFields = civicrm_api('setting', 'getfields', array(
      'version' => 3,
    ));

    $this->addYesNo('enableSSL', ts('Force Secure URLs (SSL)'));
    $this->addYesNo('verifySSL', ts('Verify SSL Certs'));
    // FIXME: verifySSL should use $_settings instead of manually adding fields
    $this->assign('verifySSL_description', $settingFields['values']['verifySSL']['description']);

    $this->addFormRule(array('CRM_Admin_Form_Setting_Url', 'formRule'));

    parent::buildQuickForm();
  }

  /**
   * @param $fields
   *
   * @return array|bool
   */
  public static function formRule($fields) {
    if (isset($fields['enableSSL']) &&
      $fields['enableSSL']
    ) {
      $config = CRM_Core_Config::singleton();
      $url = str_replace('http://', 'https://',
        CRM_Utils_System::url('civicrm/dashboard', 'reset=1', TRUE,
          NULL, FALSE, FALSE
        )
      );
      if (!CRM_Utils_System::checkURL($url, TRUE)) {
        $errors = array(
          'enableSSL' => ts('You need to set up a secure server before you can use the Force Secure URLs option'),
        );
        return $errors;
      }
    }
    return TRUE;
  }

  public function postProcess() {
    // if extensions url is set, lets clear session status messages to avoid
    // a potentially spurious message which might already have been set. This
    // is a bit hackish
    // CRM-10629
    $session = CRM_Core_Session::singleton();
    $session->getStatus(TRUE);

    parent::postProcess();

    parent::rebuildMenu();
  }

}
