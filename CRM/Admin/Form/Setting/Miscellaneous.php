<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 */

/**
 * This class generates form components for Miscellaneous.
 */
class CRM_Admin_Form_Setting_Miscellaneous extends CRM_Admin_Form_Setting {

  protected $_settings = array(
    'max_attachments' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'contact_undelete' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'dashboardCacheTimeout' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'empoweredBy' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'logging' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'maxFileSize' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'doNotAttachPDFReceipt' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'secondDegRelPermissions' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'checksum_timeout' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'recaptchaOptions' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'recaptchaPublicKey' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'recaptchaPrivateKey' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'wkhtmltopdfPath' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
  );

  public $_uploadMaxSize;

  /**
   * Basic setup.
   */
  public function preProcess() {
    $config = CRM_Core_Config::singleton();
    $this->_uploadMaxSize = (int) ini_get('upload_max_filesize');
    // check for post max size
    CRM_Utils_Number::formatUnitSize(ini_get('post_max_size'), TRUE);
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Misc (Undelete, PDFs, Limits, Logging, Captcha, etc.)'));

    $this->assign('validTriggerPermission', CRM_Core_DAO::checkTriggerViewPermission(FALSE));

    $this->addFormRule(array('CRM_Admin_Form_Setting_Miscellaneous', 'formRule'), $this);

    parent::buildQuickForm();
    $this->addRule('checksum_timeout', ts('Value should be a positive number'), 'positiveInteger');
    $this->addRule('dashboardCacheTimeout', ts('Value should be a positive number'), 'positiveInteger');
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param array $options
   *   Additional user data.
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields, $files, $options) {
    $errors = array();

    // validate max file size
    if ($fields['maxFileSize'] > $options->_uploadMaxSize) {
      $errors['maxFileSize'] = ts("Maximum file size cannot exceed Upload max size ('upload_max_filesize') as defined in PHP.ini.");
    }

    if (!empty($fields['wkhtmltopdfPath'])) {
      // check and ensure that thi leads to the wkhtmltopdf binary
      // and it is a valid executable binary
      // Only check the first space separated piece to allow for a value
      // such as /usr/bin/xvfb-run -- wkhtmltopdf (CRM-13292)
      $pieces = explode(' ', $fields['wkhtmltopdfPath']);
      $path = $pieces[0];
      if (
        !file_exists($path) ||
        !is_executable($path)
      ) {
        $errors['wkhtmltopdfPath'] = ts('The wkhtmltodfPath does not exist or is not valid');
      }
    }
    return $errors;
  }

}
