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
 * @deprecated use CRM_Admin_Form_Generic instead.
 */
class CRM_Admin_Form_Preferences extends CRM_Core_Form {

  use CRM_Admin_Form_SettingTrait;

  protected $_system = FALSE;
  protected $_contactID = NULL;
  public $_action = NULL;

  /**
   * This should only be populated programmatically via the settings metadata.
   *
   * @var array
   */
  protected $_settings = [];

  protected $_params = NULL;

  /**
   * Preprocess form.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    // @todo - it's likely the only 'current' code in this function is the line
    // $this->addFieldsDefinedInSettingsMetadata(); and this class is no different to CRM_Admin_Form_Setting
    // in any meaningful way.
    $this->_contactID = CRM_Utils_Request::retrieve('cid', 'Positive',
      $this, FALSE
    );
    $this->_system = CRM_Utils_Request::retrieve('system', 'Boolean',
      $this, FALSE, TRUE
    );
    $this->_action = CRM_Utils_Request::retrieve('action', 'String',
      $this, FALSE, 'update'
    );

    if ($this->_system) {
      if (CRM_Core_Permission::check('administer CiviCRM')) {
        $this->_contactID = NULL;
      }
      else {
        throw new CRM_Core_Exception('You do not have permission to edit preferences');
      }
    }
    else {
      if (!$this->_contactID) {
        $this->_contactID = CRM_Core_Session::getLoggedInContactID();
        if (!$this->_contactID) {
          throw new CRM_Core_Exception('Could not retrieve contact id');
        }
        $this->set('cid', $this->_contactID);
      }
    }

    $this->addFieldsDefinedInSettingsMetadata();
    CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url('civicrm/admin', 'reset=1'));
  }

  /**
   * @return array
   */
  public function setDefaultValues() {
    $this->_defaults = [];
    $this->setDefaultsForMetadataDefinedFields();
    return $this->_defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    $this->assign('entityInClassFormat', 'setting');

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

    if ($this->_action == CRM_Core_Action::VIEW) {
      $this->freeze();
    }
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    if ($this->_action == CRM_Core_Action::VIEW) {
      return;
    }

    $this->_params = $this->controller->exportValues($this->_name);

    $this->postProcessCommon();
  }

  /**
   * Process the form submission.
   */
  public function postProcessCommon() {
    try {
      $this->saveMetadataDefinedSettings($this->_params);
      $this->filterParamsSetByMetadata($this->_params);
    }
    catch (CRM_Core_Exception $e) {
      CRM_Core_Session::setStatus($e->getMessage(), ts('Save Failed'), 'error');
    }

    CRM_Core_Session::setStatus(ts('Your changes have been saved.'), ts('Saved'), 'success');
  }

}
