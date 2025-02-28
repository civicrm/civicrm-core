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
 * This class generates form components for Membership Type
 */
class CRM_Member_Form_MembershipStatus extends CRM_Core_Form {

  use CRM_Core_Form_EntityFormTrait;

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'MembershipStatus';
  }

  /**
   * Explicitly declare the form context.
   */
  public function getDefaultContext() {
    return 'create';
  }

  /**
   * Set entity fields to be assigned to the form.
   */
  protected function setEntityFields() {
    $this->entityFields = [
      'label' => [
        'name' => 'label',
        'description' => ts("Display name for this Membership status (e.g. New, Current, Grace, Expired...)."),
        'required' => TRUE,
      ],
      'is_admin' => [
        'name' => 'is_admin',
        'description' => ts("Check this box if this status is for use by administrative staff only. If checked, this status is never automatically assigned by CiviMember. It is assigned to a contact's Membership by checking the <strong>Status Override</strong> flag when adding or editing the Membership record. Start and End Event settings are ignored for Administrator statuses. EXAMPLE: This setting can be useful for special case statuses like 'Non-expiring', 'Barred' or 'Expelled', etc."),
      ],
    ];
  }

  /**
   * Set the delete message.
   *
   * We do this from the constructor in order to do a translation.
   */
  public function setDeleteMessage() {
    $this->deleteMessage = ts('You will not be able to delete this membership status if there are existing memberships with this status. You will need to check all your membership status rules afterwards to ensure that a valid status will always be available.') . " " . ts('Do you want to continue?');
  }

  public function preProcess() {
    $this->_id = $this->get('id');
    $this->_BAOName = 'CRM_Member_BAO_MembershipStatus';
  }

  /**
   * Set default values for the form. MobileProvider that in edit/view mode
   * the default values are retrieved from the database
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = $this->getEntityDefaults();

    if ($this->_action & CRM_Core_Action::ADD) {
      $defaults['is_active'] = 1;
    }

    //finding default weight to be put
    if (empty($defaults['weight'])) {
      $defaults['weight'] = CRM_Utils_Weight::getDefaultWeight('CRM_Member_DAO_MembershipStatus');
    }
    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    self::buildQuickEntityForm();
    parent::buildQuickForm();

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    if ($this->_id) {
      $name = $this->add('text', 'name', ts('Name'),
        CRM_Core_DAO::getAttribute('CRM_Member_DAO_MembershipStatus', 'name')
      );
      $name->freeze();
      $this->assign('id', $this->_id);
    }
    $this->addRule('label', ts('A membership status with this label already exists. Please select another label.'),
      'objectExists', ['CRM_Member_DAO_MembershipStatus', $this->_id, 'name']
    );

    $this->add('select', 'start_event', ts('Start Event'), CRM_Core_SelectValues::eventDate(), TRUE);
    $this->add('select', 'start_event_adjust_unit', ts('Start Event Adjustment'), ['' => ts('- select -')] + CRM_Core_SelectValues::unitList());
    $this->add('text', 'start_event_adjust_interval', ts('Start Event Adjust Interval'),
      CRM_Core_DAO::getAttribute('CRM_Member_DAO_MembershipStatus', 'start_event_adjust_interval')
    );
    $this->add('select', 'end_event', ts('End Event'), ['' => ts('- select -')] + CRM_Core_SelectValues::eventDate());
    $this->add('select', 'end_event_adjust_unit', ts('End Event Adjustment'), ['' => ts('- select -')] + CRM_Core_SelectValues::unitList());
    $this->add('text', 'end_event_adjust_interval', ts('End Event Adjust Interval'),
      CRM_Core_DAO::getAttribute('CRM_Member_DAO_MembershipStatus', 'end_event_adjust_interval')
    );
    $this->add('checkbox', 'is_current_member', ts('Current Membership?'));

    $this->add('number', 'weight', ts('Order'),
      CRM_Core_DAO::getAttribute('CRM_Member_DAO_MembershipStatus', 'weight')
    );
    $this->add('checkbox', 'is_default', ts('Default?'));
    $this->add('checkbox', 'is_active', ts('Enabled?'));
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      try {
        CRM_Member_BAO_MembershipStatus::deleteRecord(['id' => $this->_id]);
      }
      catch (CRM_Core_Exception $e) {
        CRM_Core_Error::statusBounce($e->getMessage(), NULL, ts('Delete Failed'));
      }
      CRM_Core_Session::setStatus(ts('Selected membership status has been deleted.'), ts('Record Deleted'), 'success');
    }
    else {
      // store the submitted values in an array
      $params = $this->exportValues();
      $params['is_active'] = $params['is_active'] ?? FALSE;
      $params['is_current_member'] = $params['is_current_member'] ?? FALSE;
      $params['is_admin'] = $params['is_admin'] ?? FALSE;
      $params['is_default'] = $params['is_default'] ?? FALSE;

      if ($this->_action & CRM_Core_Action::UPDATE) {
        $params['id'] = $this->getEntityId();
      }
      $oldWeight = NULL;
      if ($this->_id) {
        $oldWeight = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipStatus', $this->_id, 'weight', 'id');
      }
      $params['weight'] = CRM_Utils_Weight::updateOtherWeights('CRM_Member_DAO_MembershipStatus', $oldWeight, $params['weight']);

      // only for add mode, set label to name.
      if ($this->_action & CRM_Core_Action::ADD) {
        $params['name'] = $params['label'];
      }

      $membershipStatus = CRM_Member_BAO_MembershipStatus::add($params);
      CRM_Core_Session::setStatus(ts('The membership status \'%1\' has been saved.',
        [1 => $membershipStatus->label]
      ), ts('Saved'), 'success');
    }
  }

}
