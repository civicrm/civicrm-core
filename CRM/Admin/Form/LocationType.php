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
   * @var bool
   */
  public $submitOnce = TRUE;

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'LocationType';
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

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

    $this->add('advcheckbox', 'is_active', ts('Enabled?'));
    $this->add('advcheckbox', 'is_default', ts('Default?'));

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
    // Delete action
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Core_BAO_LocationType::deleteRecord(['id' => $this->_id]);
      CRM_Core_Session::setStatus(ts('Selected Location type has been deleted.'), ts('Record Deleted'), 'success');
      return;
    }
    // Create or update actions
    $params = $this->exportValues();
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $params['id'] = $this->_id;
    }

    $locationType = CRM_Core_BAO_LocationType::writeRecord($params);

    CRM_Core_Session::setStatus(ts("The location type '%1' has been saved.",
      [1 => $locationType->name]
    ), ts('Saved'), 'success');
  }

}
