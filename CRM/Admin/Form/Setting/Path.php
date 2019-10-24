<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
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
