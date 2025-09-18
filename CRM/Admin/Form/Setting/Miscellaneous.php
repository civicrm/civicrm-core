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

  /**
   * Basic setup.
   */
  public function preProcess(): void {
    $maxImportFileSize = CRM_Utils_Number::formatUnitSize(ini_get('upload_max_filesize'));
    $postMaxSize = CRM_Utils_Number::formatUnitSize(ini_get('post_max_size'));
    if ($maxImportFileSize > $postMaxSize) {
      CRM_Core_Session::setStatus(ts("Note: Upload max filesize ('upload_max_filesize') should not exceed Post max size ('post_max_size') as defined in PHP.ini, please check with your system administrator."), ts("Warning"), "alert");
    }
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->setTitle(ts('Misc (Undelete, PDFs, Limits, Logging, etc.)'));

    $this->addFormRule(['CRM_Admin_Form_Setting_Miscellaneous', 'formRule'], $this);

    parent::buildQuickForm();

    $settingMetaData = $this->getSettingsMetaData();

    // Disable logging field if system does not meet requirements
    if (CRM_Core_I18n::isMultilingual()) {
      $settingMetaData['logging']['description'] = ts('Logging is not supported in multilingual environments.');
      $this->freeze('logging');
    }
    elseif (!CRM_Core_DAO::checkTriggerViewPermission(FALSE)) {
      $settingMetaData['logging']['description'] = ts("In order to use this functionality, the installation's database user must have privileges to create triggers (in MySQL 5.0 – and in MySQL 5.1 if binary logging is enabled – this means the SUPER privilege). This install either does not seem to have the required privilege enabled.");
      $this->freeze('logging');
    }

    $this->assign('settings_fields', $settingMetaData);

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
    $iniBytes = CRM_Utils_Number::formatUnitSize(ini_get('upload_max_filesize'));
    $inputBytes = ((int) $fields['maxFileSize']) * 1024 * 1024;

    if ($inputBytes > $iniBytes) {
      $errors['maxFileSize'] = ts("Maximum file size cannot exceed limit defined in \"php.ini\" (\"upload_max_filesize=%1\").", [
        1 => ini_get('upload_max_filesize'),
      ]);
    }

    // validate recent items stack size
    if ($fields['recentItemsMaxCount'] && ($fields['recentItemsMaxCount'] < 1 || $fields['recentItemsMaxCount'] > CRM_Utils_Recent::MAX_ITEMS)) {
      $errors['recentItemsMaxCount'] = ts("Illegal stack size. Use values between 1 and %1.", [1 => CRM_Utils_Recent::MAX_ITEMS]);
    }

    if (!empty($fields['weasyprint_path'])) {
      // check and ensure that this path leads to the weasyprint binary
      // and it is a valid executable binary
      // Only check the first space separated piece to allow for a value
      // such as /usr/bin/xvfb-run -- weasyprint (CRM-13292)
      $pieces = explode(' ', $fields['weasyprint_path']);
      $path = $pieces[0];
      if (
        !file_exists($path) ||
        !is_executable($path)
      ) {
        $errors['weasyprint_path'] = ts('The path for %1 does not exist or is not valid', [1 => 'weasyprint']);
      }
    }
    if (!empty($fields['wkhtmltopdfPath'])) {
      // check and ensure that this path leads to the wkhtmltopdf binary
      // and it is a valid executable binary
      // Only check the first space separated piece to allow for a value
      // such as /usr/bin/xvfb-run -- wkhtmltopdf (CRM-13292)
      $pieces = explode(' ', $fields['wkhtmltopdfPath']);
      $path = $pieces[0];
      if (
        !file_exists($path) ||
        !is_executable($path)
      ) {
        $errors['wkhtmltopdfPath'] = ts('The path for %1 does not exist or is not valid', [1 => 'wkhtmltopdf']);
      }
    }
    return $errors;
  }

}
