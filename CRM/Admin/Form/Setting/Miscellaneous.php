<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * This class generates form components for Miscellaneous
 *
 */
class CRM_Admin_Form_Setting_Miscellaneous extends CRM_Admin_Form_Setting {

  protected $_settings = array(
    'max_attachments' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'contact_undelete' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'versionAlert' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'versionCheck' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'empoweredBy' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'maxFileSize' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'doNotAttachPDFReceipt' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'secondDegRelPermissions' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'checksumTimeout' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
  );

  public $_uploadMaxSize;

  /**
   * Basic setup
   */

  public function preProcess() {
    $config     = CRM_Core_Config::singleton();
    $this->_uploadMaxSize = (int) ini_get('upload_max_filesize');
    // check for post max size
    CRM_Core_Config_Defaults::formatUnitSize(ini_get('post_max_size'), TRUE);
  }

  /**
   * Function to build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Misc (Undelete, PDFs, Limits, Logging, Captcha, etc.)'));

    // also check if we can enable triggers
    $validTriggerPermission = CRM_Core_DAO::checkTriggerViewPermission(FALSE);

    // FIXME: for now, disable logging for multilingual sites OR if triggers are not permittted
    $domain = new CRM_Core_DAO_Domain;
    $domain->find(TRUE);
    $attribs = $domain->locales || !$validTriggerPermission ?
      array('disabled' => 'disabled') : array();

    $this->assign('validTriggerPermission', $validTriggerPermission);
    $this->addYesNo('logging', ts('Logging'), NULL, NULL, $attribs);

    $this->addElement(
      'text',
      'wkhtmltopdfPath', ts('Path to wkhtmltopdf executable'),
      array('size' => 64, 'maxlength' => 256)
    );

    $this->addElement(
      'text', 'recaptchaPublicKey', ts('Public Key'),
      array('size' => 64, 'maxlength' => 64)
    );
    $this->addElement(
      'text', 'recaptchaPrivateKey', ts('Private Key'),
      array('size' => 64, 'maxlength' => 64)
    );

    $this->addElement(
      'text', 'dashboardCacheTimeout', ts('Dashboard cache timeout'),
      array('size' => 3, 'maxlength' => 5)
    );

    $this->addElement(
      'text', 'recaptchaOptions', ts('Recaptcha Options'),
      array('size' => 64, 'maxlength' => 64)
    );

    $this->addFormRule(array('CRM_Admin_Form_Setting_Miscellaneous', 'formRule'), $this);

    parent::buildQuickForm();
    $this->addRule('checksumTimeout', ts('Value should be a positive number'), 'positiveInteger');
  }

  /**
   * global form rule
   *
   * @param array $fields  the input form values
   * @param array $files   the uploaded files if any
   * @param array $options additional user data
   *
   * @return true if no errors, else array of errors
   * @access public
   * @static
   */
  static function formRule($fields, $files, $options) {
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


  public function postProcess() {
    // store the submitted values in an array
    $config = CRM_Core_Config::singleton();
    $params = $this->controller->exportValues($this->_name);

    // update upload max size in DB
    $params['maxImportFileSize'] = CRM_Core_Config_Defaults::formatUnitSize(ini_get('upload_max_filesize'));
    CRM_Core_BAO_ConfigSetting::create($params);

    // get current logging status
    $values = $this->exportValues();

    parent::postProcess();

    if ($config->logging != $values['logging']) {
      $logging = new CRM_Logging_Schema;
      if ($values['logging']) {
        $logging->enableLogging();
      }
      else {
        $logging->disableLogging();
      }
    }
  }
}

