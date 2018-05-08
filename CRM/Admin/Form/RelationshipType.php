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
 * This class generates form components for Relationship Type.
 */
class CRM_Admin_Form_RelationshipType extends CRM_Admin_Form {

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'RelationshipType';
  }

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

    $this->addField('label_a_b');
    $this->addField('label_b_a');
    $this->addRule('label_a_b', ts('Label already exists in Database.'),
      'objectExists', array('CRM_Contact_DAO_RelationshipType', $this->_id, 'label_a_b')
    );
    $this->addRule('label_b_a', ts('Label already exists in Database.'),
      'objectExists', array('CRM_Contact_DAO_RelationshipType', $this->_id, 'label_b_a')
    );

    $this->addField('description');

    $contactTypes = CRM_Contact_BAO_ContactType::getSelectElements(FALSE, TRUE, '__');

    // add select for contact type
    $this->add('select', 'contact_types_a', ts('Contact Type A') . ' ',
      array(
        '' => ts('All Contacts'),
      ) + $contactTypes
    );
    $this->add('select', 'contact_types_b', ts('Contact Type B') . ' ',
      array(
        '' => ts('All Contacts'),
      ) + $contactTypes
    );

    $this->addField('is_active');

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

      $defaults['contact_types_b'] = CRM_Utils_Array::value('contact_type_b', $defaults);
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
      // store the submitted values in an array
      $params = $this->exportValues();
      $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);

      if ($this->_action & CRM_Core_Action::UPDATE) {
        $params['id'] = $this->_id;
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

      $params['contact_sub_type_a'] = $cTypeA[1] ? $cTypeA[1] : 'null';
      $params['contact_sub_type_b'] = $cTypeB[1] ? $cTypeB[1] : 'null';

      if (!strlen(trim(CRM_Utils_Array::value('label_b_a', $params)))) {
        $params['label_b_a'] = CRM_Utils_Array::value('label_a_b', $params);
      }

      if (empty($params['id'])) {
        // Set name on created but don't update on update as the machine name is not exposed.
        $params['name_b_a'] = CRM_Utils_String::munge($params['label_b_a']);
        $params['name_a_b'] = CRM_Utils_String::munge($params['label_a_b']);
      }

      $result = civicrm_api3('RelationshipType', 'create', $params);

      $this->ajaxResponse['relationshipType'] = $result['values'];

      CRM_Core_Session::setStatus(ts('The Relationship Type has been saved.'), ts('Saved'), 'success');
    }
  }

}
