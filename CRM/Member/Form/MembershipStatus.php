<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 * $Id$
 *
 */

/**
 * This class generates form components for Membership Type
 *
 */
class CRM_Member_Form_MembershipStatus extends CRM_Member_Form_MembershipConfig {

  /**
   * Set default values for the form. MobileProvider that in edit/view mode
   * the default values are retrieved from the database
   *
   *
   * @return void
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();

    //finding default weight to be put
    if (empty($defaults['weight'])) {
      $defaults['weight'] = CRM_Utils_Weight::getDefaultWeight('CRM_Member_DAO_MembershipStatus');
    }
    return $defaults;
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $this->applyFilter('__ALL__', 'trim');

    if ($this->_id) {
      $name = $this->add('text', 'name', ts('Name'),
        CRM_Core_DAO::getAttribute('CRM_Member_DAO_MembershipStatus', 'name')
      );
      $name->freeze();
      $this->assign('id', $this->_id);
    }
    $this->add('text', 'label', ts('Label'),
      CRM_Core_DAO::getAttribute('CRM_Member_DAO_MembershipStatus', 'label'), TRUE
    );
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
    $this->add('checkbox', 'is_admin', ts('Administrator Only?'));

    $this->add('text', 'weight', ts('Order'),
      CRM_Core_DAO::getAttribute('CRM_Member_DAO_MembershipStatus', 'weight')
    );
    $this->add('checkbox', 'is_default', ts('Default?'));
    $this->add('checkbox', 'is_active', ts('Enabled?'));
  }

  /**
   * Process the form submission.
   *
   *
   * @return void
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
      $params = $ids = array();
      // store the submitted values in an array
      $params = $this->exportValues();
      $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
      $params['is_current_member'] = CRM_Utils_Array::value('is_current_member', $params, FALSE);
      $params['is_admin'] = CRM_Utils_Array::value('is_admin', $params, FALSE);
      $params['is_default'] = CRM_Utils_Array::value('is_default', $params, FALSE);

      if ($this->_action & CRM_Core_Action::UPDATE) {
        $ids['membershipStatus'] = $this->_id;
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

      $membershipStatus = CRM_Member_BAO_MembershipStatus::add($params, $ids);
      CRM_Core_Session::setStatus(ts('The membership status \'%1\' has been saved.',
        array(1 => $membershipStatus->label)
      ), ts('Saved'), 'success');
    }
  }

}
