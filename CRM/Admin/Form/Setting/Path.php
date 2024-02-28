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
    'uploadDir' => CRM_Core_BAO_Setting::DIRECTORY_PREFERENCES_NAME,
    'imageUploadDir' => CRM_Core_BAO_Setting::DIRECTORY_PREFERENCES_NAME,
    'customFileUploadDir' => CRM_Core_BAO_Setting::DIRECTORY_PREFERENCES_NAME,
    'customTemplateDir' => CRM_Core_BAO_Setting::DIRECTORY_PREFERENCES_NAME,
    'customPHPPathDir' => CRM_Core_BAO_Setting::DIRECTORY_PREFERENCES_NAME,
    'extensionsDir' => CRM_Core_BAO_Setting::DIRECTORY_PREFERENCES_NAME,
    'ext_max_depth' => CRM_Core_BAO_Setting::EXT,
  ];

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm() {
    $this->setTitle(ts('Settings - Upload Directories'));
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

}
