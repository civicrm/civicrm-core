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
 * Mostly unused shell of a form. Delete this file after doing something with the weird status check.
 */
class CRM_Admin_Form_Setting_Miscellaneous extends CRM_Admin_Form_SettingPage {

  public function getTemplateFileName() {
    return 'CRM/Admin/Form/SettingPage.tpl';
  }

  /**
   * Basic setup.
   */
  public function preProcess(): void {
    // FIXME: This is a weird place to check PHP settings. If anything, this ought to be a status check.
    $maxImportFileSize = CRM_Utils_Number::formatUnitSize(ini_get('upload_max_filesize'));
    $postMaxSize = CRM_Utils_Number::formatUnitSize(ini_get('post_max_size'));
    if ($maxImportFileSize > $postMaxSize) {
      CRM_Core_Session::setStatus(ts("Note: Upload max filesize ('upload_max_filesize') should not exceed Post max size ('post_max_size') as defined in PHP.ini, please check with your system administrator."), ts("Warning"), "alert");
    }
    parent::preProcess();
  }

}
