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
 * This class generates form components for Miscellaneous.
 */
class CRM_Admin_Form_Setting_Miscellaneous extends CRM_Admin_Form_Setting {

  protected $_settings = [
    'max_attachments' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'max_attachments_backend' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'contact_undelete' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'empoweredBy' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'logging' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'maxFileSize' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'doNotAttachPDFReceipt' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'recordGeneratedLetters' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'secondDegRelPermissions' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'checksum_timeout' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'recaptchaOptions' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'recaptchaPublicKey' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'recaptchaPrivateKey' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'forceRecaptcha' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'wkhtmltopdfPath' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'recentItemsMaxCount' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'recentItemsProviders' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'dedupe_default_limit' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'remote_profile_submissions' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'allow_alert_autodismissal' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'prevNextBackend' => CRM_Core_BAO_Setting::SEARCH_PREFERENCES_NAME,
  ];

  public $_uploadMaxSize;

  /**
   * Basic setup.
   */
  public function preProcess() {
    $this->_uploadMaxSize = (int) ini_get('upload_max_filesize');
    // check for post max size
    CRM_Utils_Number::formatUnitSize(ini_get('post_max_size'), TRUE);
    // This is a temp hack for the fact we really don't need to hard-code each setting in the tpl but
    // we haven't worked through NOT doing that. These settings have been un-hardcoded.
    $this->assign('pure_config_settings', [
      'empoweredBy',
      'max_attachments',
      'max_attachments_backend',
      'maxFileSize',
      'secondDegRelPermissions',
      'recentItemsMaxCount',
      'recentItemsProviders',
      'dedupe_default_limit',
      'prevNextBackend',
    ]);
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Misc (Undelete, PDFs, Limits, Logging, Captcha, etc.)'));

    $this->assign('validTriggerPermission', CRM_Core_DAO::checkTriggerViewPermission(FALSE));

    $this->addFormRule(['CRM_Admin_Form_Setting_Miscellaneous', 'formRule'], $this);

    parent::buildQuickForm();
    $this->addRule('checksum_timeout', ts('Value should be a positive number'), 'positiveInteger');
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
    $errors = [];

    // validate max file size
    if ($fields['maxFileSize'] > $options->_uploadMaxSize) {
      $errors['maxFileSize'] = ts("Maximum file size cannot exceed Upload max size ('upload_max_filesize') as defined in PHP.ini.");
    }

    // validate recent items stack size
    if ($fields['recentItemsMaxCount'] && ($fields['recentItemsMaxCount'] < 1 || $fields['recentItemsMaxCount'] > CRM_Utils_Recent::MAX_ITEMS)) {
      $errors['recentItemsMaxCount'] = ts("Illegal stack size. Use values between 1 and %1.", [1 => CRM_Utils_Recent::MAX_ITEMS]);
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
