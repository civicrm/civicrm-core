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
 * This class generates form components for Location Type.
 */
class CRM_Admin_Form_LocationType extends CRM_Admin_Form {

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    $this->setPageTitle(ts('Location Type'));

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $this->applyFilter('__ALL__', 'trim');
    $this->add('text',
      'name',
      ts('Name'),
      CRM_Core_DAO::getAttribute('CRM_Core_DAO_LocationType', 'name'),
      TRUE
    );
    $this->addRule('name',
      ts('Name already exists in Database.'),
      'objectExists',
      ['CRM_Core_DAO_LocationType', $this->_id]
    );
    $this->addRule('name',
      ts('Name can only consist of alpha-numeric characters'),
      'variable'
    );

    $this->add('text', 'display_name', ts('Display Name'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_LocationType', 'display_name'), TRUE);
    $this->add('text', 'vcard_name', ts('vCard Name'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_LocationType', 'vcard_name'));

    $this->add('text', 'description', ts('Description'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_LocationType', 'description'));

    $this->add('checkbox', 'is_active', ts('Enabled?'));
    $this->add('checkbox', 'is_default', ts('Default?'));

    if ($this->_action & CRM_Core_Action::UPDATE) {
      if (CRM_Core_DAO::getFieldValue('CRM_Core_DAO_LocationType', $this->_id, 'is_reserved')) {
        $this->freeze(['name', 'description', 'is_active']);
      }
      if (CRM_Core_DAO::getFieldValue('CRM_Core_DAO_LocationType', $this->_id, 'is_default')) {
        $this->freeze(['is_default']);
      }
    }
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    CRM_Utils_System::flushCache();

    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Core_BAO_LocationType::del($this->_id);
      CRM_Core_Session::setStatus(ts('Selected Location type has been deleted.'), ts('Record Deleted'), 'success');
      return;
    }

    // store the submitted values in an array
    $params = $this->exportValues();
    $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
    $params['is_default'] = CRM_Utils_Array::value('is_default', $params, FALSE);

    // action is taken depending upon the mode
    $locationType = new CRM_Core_DAO_LocationType();
    $locationType->name = $params['name'];
    $locationType->display_name = $params['display_name'];
    $locationType->vcard_name = $params['vcard_name'];
    $locationType->description = $params['description'];
    $locationType->is_active = $params['is_active'];
    $locationType->is_default = $params['is_default'];

    if ($params['is_default']) {
      $query = "UPDATE civicrm_location_type SET is_default = 0";
      CRM_Core_DAO::executeQuery($query);
    }

    if ($this->_action & CRM_Core_Action::UPDATE) {
      $locationType->id = $this->_id;
    }

    $locationType->save();

    CRM_Core_Session::setStatus(ts("The location type '%1' has been saved.",
      [1 => $locationType->name]
    ), ts('Saved'), 'success');
  }

}
