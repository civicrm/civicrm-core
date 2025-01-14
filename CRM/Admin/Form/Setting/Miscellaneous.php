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
   * Subset of settings on the page as defined using the legacy method.
   *
   * @var array
   *
   * @deprecated - do not add new settings here - the page to display
   * settings on should be defined in the setting metadata.
   */
  protected $_settings = [
    // @todo remove these, define any not yet defined in the setting metadata.
    'max_attachments' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'max_attachments_backend' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'contact_undelete' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'empoweredBy' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'logging' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'enableBackgroundQueue' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'maxFileSize' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'doNotAttachPDFReceipt' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'recordGeneratedLetters' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'secondDegRelPermissions' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'checksum_timeout' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'dompdf_font_dir' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'dompdf_chroot' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'dompdf_enable_remote' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'weasyprint_path' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'wkhtmltopdfPath' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'recentItemsMaxCount' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'recentItemsProviders' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'dedupe_default_limit' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'remote_profile_submissions' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'allow_alert_autodismissal' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'prevNextBackend' => CRM_Core_BAO_Setting::SEARCH_PREFERENCES_NAME,
    'import_batch_size' => CRM_Core_BAO_Setting::SEARCH_PREFERENCES_NAME,
    'disable_sql_memory_engine' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
  ];

  /**
   * Basic setup.
   */
  public function preProcess(): void {
    $maxImportFileSize = CRM_Utils_Number::formatUnitSize(ini_get('upload_max_filesize'));
    $postMaxSize = CRM_Utils_Number::formatUnitSize(ini_get('post_max_size'));
    if ($maxImportFileSize > $postMaxSize) {
      CRM_Core_Session::setStatus(ts("Note: Upload max filesize ('upload_max_filesize') should not exceed Post max size ('post_max_size') as defined in PHP.ini, please check with your system administrator."), ts("Warning"), "alert");
    }

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
      'import_batch_size',
      'disable_sql_memory_engine',
    ]);
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->setTitle(ts('Misc (Undelete, PDFs, Limits, Logging, etc.)'));

    $this->assign('validTriggerPermission', CRM_Core_DAO::checkTriggerViewPermission(FALSE));
    // dev/core#1812 Assign multilingual status.
    $this->assign('isMultilingual', CRM_Core_I18n::isMultilingual());

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
