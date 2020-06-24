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
