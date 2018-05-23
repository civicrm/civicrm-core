<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
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
   * Fields for the entity to be assigned to the template.
   *
   * Fields may have keys
   *  - name (required to show in tpl from the array)
   *  - description (optional, will appear below the field)
   *  - not-auto-addable - this class will not attempt to add the field using addField.
   *    (this will be automatically set if the field does not have html in it's metadata
   *    or is not a core field on the form's entity).
   *  - help (optional) add help to the field - e.g ['id' => 'id-source', 'file' => 'CRM/Contact/Form/Contact']]
   *  - template - use a field specific template to render this field
   *  - required
   * @var array
   */
  protected $entityFields = [];

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
    $this->deleteMessage = ts('You will not be able to delete this membership status if there are existing memberships with this status. You will need to check all your membership status rules afterwards to ensure that a valid status will always be available.') . " "  . ts('Do you want to continue?');
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
      'objectExists', array('CRM_Member_DAO_MembershipStatus', $this->_id, 'name')
    );

    $this->add('select', 'start_event', ts('Start Event'), CRM_Core_SelectValues::eventDate(), TRUE);
    $this->add('select', 'start_event_adjust_unit', ts('Start Event Adjustment'), array('' => ts('- select -')) + CRM_Core_SelectValues::unitList());
    $this->add('text', 'start_event_adjust_interval', ts('Start Event Adjust Interval'),
      CRM_Core_DAO::getAttribute('CRM_Member_DAO_MembershipStatus', 'start_event_adjust_interval')
    );
    $this->add('select', 'end_event', ts('End Event'), array('' => ts('- select -')) + CRM_Core_SelectValues::eventDate());
    $this->add('select', 'end_event_adjust_unit', ts('End Event Adjustment'), array('' => ts('- select -')) + CRM_Core_SelectValues::unitList());
    $this->add('text', 'end_event_adjust_interval', ts('End Event Adjust Interval'),
      CRM_Core_DAO::getAttribute('CRM_Member_DAO_MembershipStatus', 'end_event_adjust_interval')
    );
    $this->add('checkbox', 'is_current_member', ts('Current Membership?'));

    $this->add('text', 'weight', ts('Order'),
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
        CRM_Member_BAO_MembershipStatus::del($this->_id);
      }
      catch (CRM_Core_Exception $e) {
        CRM_Core_Error::statusBounce($e->getMessage(), NULL, ts('Delete Failed'));
      }
      CRM_Core_Session::setStatus(ts('Selected membership status has been deleted.'), ts('Record Deleted'), 'success');
    }
    else {
      // store the submitted values in an array
      $params = $this->exportValues();
      $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
      $params['is_current_member'] = CRM_Utils_Array::value('is_current_member', $params, FALSE);
      $params['is_admin'] = CRM_Utils_Array::value('is_admin', $params, FALSE);
      $params['is_default'] = CRM_Utils_Array::value('is_default', $params, FALSE);

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
        array(1 => $membershipStatus->label)
      ), ts('Saved'), 'success');
    }
  }

}
