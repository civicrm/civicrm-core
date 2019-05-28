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
 * Generic metadata based settings form.
 *
 * The form filter will determine the settings displayed.
 */
class CRM_Admin_Form_Generic extends CRM_Core_Form {
  use CRM_Admin_Form_SettingTrait;

  protected $_settings = [];
  protected $includesReadOnlyFields = FALSE;
  public $_defaults = [];

  /**
   * Get the tpl file name.
   *
   * @return string
   */
  public function getTemplateFileName() {
    return 'CRM/Form/basicForm.tpl';
  }

  /**
   * Set default values for the form.
   *
   * Default values are retrieved from the database.
   */
  public function setDefaultValues() {
    $this->setDefaultsForMetadataDefinedFields();
    return $this->_defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $filter = $this->getSettingPageFilter();
    $settings = civicrm_api3('Setting', 'getfields', [])['values'];
    foreach ($settings as $key => $setting) {
      if (isset($setting['settings_pages'][$filter])) {
        $this->_settings[$key] = $setting;
      }
    }

    $this->addFieldsDefinedInSettingsMetadata();

    // @todo look at sharing the code below in the settings trait.
    if ($this->includesReadOnlyFields) {
      CRM_Core_Session::setStatus(ts("Some fields are loaded as 'readonly' as they have been set (overridden) in civicrm.settings.php."), '', 'info', ['expires' => 0]);
    }

    // @todo - do we still like this redirect?
    CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url('civicrm/admin', 'reset=1'));
    $this->addButtons([
      [
        'type' => 'next',
        'name' => ts('Save'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    try {
      $this->saveMetadataDefinedSettings($params);
    }
    catch (CiviCRM_API3_Exception $e) {
      CRM_Core_Session::setStatus($e->getMessage(), ts('Save Failed'), 'error');
    }
  }

}
