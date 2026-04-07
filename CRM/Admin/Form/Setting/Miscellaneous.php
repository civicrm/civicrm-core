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
class CRM_Admin_Form_Setting_Miscellaneous extends CRM_Admin_Form_Generic {

  public function getTemplateFileName() {
    return 'CRM/Admin/Form/Generic.tpl';
  }

  /**
   * Basic setup.
   */
  public function preProcess(): void {
    parent::preProcess();
    $this->sections = [
      'history' => [
        'title' => ts('History'),
        'icon' => 'fa-hourglass',
        'weight' => 10,
      ],
      'performance' => [
        'title' => ts('Performance'),
        'icon' => 'fa-gauge',
        'weight' => 20,
      ],
      'security' => [
        'title' => ts('Security'),
        'icon' => 'fa-lock',
        'weight' => 30,
      ],
      'files' => [
        'title' => ts('File Attachments'),
        'icon' => 'fa-paperclip',
        'weight' => 40,
      ],
      'pdf' => [
        'title' => ts('PDF Settings'),
        'icon' => 'fa-file-pdf',
        'weight' => 50,
      ],
    ];
    // FIXME: This is a weird place to check PHP settings. If anything, this ought to be a status check.
    $maxImportFileSize = CRM_Utils_Number::formatUnitSize(ini_get('upload_max_filesize'));
    $postMaxSize = CRM_Utils_Number::formatUnitSize(ini_get('post_max_size'));
    if ($maxImportFileSize > $postMaxSize) {
      CRM_Core_Session::setStatus(ts("Note: Upload max filesize ('upload_max_filesize') should not exceed Post max size ('post_max_size') as defined in PHP.ini, please check with your system administrator."), ts("Warning"), "alert");
    }
  }

}
