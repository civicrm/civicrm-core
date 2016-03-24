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
 */

/**
 * This class generates form components for Relationship Type.
 */
class CRM_Admin_Form_RelationshipType extends CRM_Admin_Form {

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    $this->setPageTitle(ts('Relationship Type'));

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $this->applyFilter('__ALL__', 'trim');

    $this->add('text', 'label_a_b', ts('Relationship Label-A to B'),
      CRM_Core_DAO::getAttribute('CRM_Contact_DAO_RelationshipType', 'label_a_b'), TRUE
    );
    $this->addRule('label_a_b', ts('Label already exists in Database.'),
      'objectExists', array('CRM_Contact_DAO_RelationshipType', $this->_id, 'label_a_b')
    );

    $this->add('text', 'label_b_a', ts('Relationship Label-B to A'),
      CRM_Core_DAO::getAttribute('CRM_Contact_DAO_RelationshipType', 'label_b_a')
    );

    $this->addRule('label_b_a', ts('Label already exists in Database.'),
      'objectExists', array('CRM_Contact_DAO_RelationshipType', $this->_id, 'label_b_a')
    );

    $this->add('text', 'description', ts('Description'),
      CRM_Core_DAO::getAttribute('CRM_Contact_DAO_RelationshipType', 'description')
    );

    $contactTypes = CRM_Contact_BAO_ContactType::getSelectElements(FALSE, TRUE, '__');

    // add select for contact type
    $contactTypeA = &$this->add('select', 'contact_types_a', ts('Contact Type A') . ' ',
      array(
        '' => ts('All Contacts'),
      ) + $contactTypes
    );
    $contactTypeB = &$this->add('select', 'contact_types_b', ts('Contact Type B') . ' ',
      array(
        '' => ts('All Contacts'),
      ) + $contactTypes
    );

    $isActive = &$this->add('checkbox', 'is_active', ts('Enabled?'));

    //only selected field should be allow for edit, CRM-4888
    if ($this->_id &&
      CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_RelationshipType', $this->_id, 'is_reserved')
    ) {
      foreach (array('contactTypeA', 'contactTypeB', 'isActive') as $field) {
        $$field->freeze();
      }
    }

    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->freeze();
    }

    $this->assign('relationship_type_id', $this->_id);

  }

  /**
   * @return array
   */
  public function setDefaultValues() {
    if ($this->_action != CRM_Core_Action::DELETE &&
      isset($this->_id)
    ) {
      $defaults = $params = array();
      $params = array('id' => $this->_id);
      $baoName = $this->_BAOName;
      $baoName::retrieve($params, $defaults);
      $defaults['contact_types_a'] = CRM_Utils_Array::value('contact_type_a', $defaults);
      if (!empty($defaults['contact_sub_type_a'])) {
        $defaults['contact_types_a'] .= '__' . $defaults['contact_sub_type_a'];
      }

      $defaults['contact_types_b'] = $defaults['contact_type_b'];
      if (!empty($defaults['contact_sub_type_b'])) {
        $defaults['contact_types_b'] .= '__' . $defaults['contact_sub_type_b'];
      }
      return $defaults;
    }
    else {
      return parent::setDefaultValues();
    }
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Contact_BAO_RelationshipType::del($this->_id);
      CRM_Core_Session::setStatus(ts('Selected Relationship type has been deleted.'), ts('Record Deleted'), 'success');
    }
    else {
      $params = array();
      $ids = array();

      // store the submitted values in an array
      $params = $this->exportValues();
      $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);

      if ($this->_action & CRM_Core_Action::UPDATE) {
        $ids['relationshipType'] = $this->_id;
      }

      $cTypeA = CRM_Utils_System::explode('__',
        $params['contact_types_a'],
        2
      );
      $cTypeB = CRM_Utils_System::explode('__',
        $params['contact_types_b'],
        2
      );

      $params['contact_type_a'] = $cTypeA[0];
      $params['contact_type_b'] = $cTypeB[0];

      $params['contact_sub_type_a'] = $cTypeA[1] ? $cTypeA[1] : 'NULL';
      $params['contact_sub_type_b'] = $cTypeB[1] ? $cTypeB[1] : 'NULL';

      CRM_Contact_BAO_RelationshipType::add($params, $ids);

      CRM_Core_Session::setStatus(ts('The Relationship Type has been saved.'), ts('Saved'), 'success');
    }
  }

}
