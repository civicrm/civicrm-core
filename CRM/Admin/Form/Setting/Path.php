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
 * This class generates form components for File System Path.
 */
class CRM_Admin_Form_Setting_Path extends CRM_Admin_Form_Setting {

  protected $_settings = [
    'uploadDir' => CRM_Core_BAO_Setting::DIRECTORY_PREFERENCES_NAME,
    'imageUploadDir' => CRM_Core_BAO_Setting::DIRECTORY_PREFERENCES_NAME,
    'customFileUploadDir' => CRM_Core_BAO_Setting::DIRECTORY_PREFERENCES_NAME,
    'customTemplateDir' => CRM_Core_BAO_Setting::DIRECTORY_PREFERENCES_NAME,
    'customPHPPathDir' => CRM_Core_BAO_Setting::DIRECTORY_PREFERENCES_NAME,
    'extensionsDir' => CRM_Core_BAO_Setting::DIRECTORY_PREFERENCES_NAME,
  ];

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Settings - Upload Directories'));
    parent::buildQuickForm();

    $directories = [
      'uploadDir' => ts('Temporary Files'),
      'imageUploadDir' => ts('Images'),
      'customFileUploadDir' => ts('Custom Files'),
      'customTemplateDir' => ts('Custom Templates'),
      'customPHPPathDir' => ts('Custom PHP Path Directory'),
      'extensionsDir' => ts('CiviCRM Extensions Directory'),
    ];
    foreach ($directories as $name => $title) {
      //$this->add('text', $name, $title);
      $this->addRule($name,
        ts("'%1' directory does not exist",
          [1 => $title]
        ),
        'settingPath'
      );
    }

  }

  public function postProcess() {
    parent::postProcess();

    parent::rebuildMenu();
  }

}
