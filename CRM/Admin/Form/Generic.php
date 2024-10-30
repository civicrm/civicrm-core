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

  /**
   * @var bool
   */
  public $submitOnce = TRUE;

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
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm() {
    $this->assign('entityInClassFormat', 'setting');
    $this->addFieldsDefinedInSettingsMetadata();

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
    catch (CRM_Core_Exception $e) {
      CRM_Core_Session::setStatus($e->getMessage(), ts('Save Failed'), 'error');
    }
  }

}
