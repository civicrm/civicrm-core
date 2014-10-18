<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * This class provides the functionality to add contact(s) to Organization
 */
class CRM_Contact_Form_Task_AddToOrganization extends CRM_Contact_Form_Task {

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  function preProcess() {
    // initialize the task and row fields
    parent::preProcess();
  }

  /**
   * Function to build the form
   *
   * @access public
   *
   * @return void
   */
  function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Add Contacts to Organization'));
    $this->addElement('text', 'name', ts('Find Target Organization'));

    $this->add('select',
      'relationship_type_id',
      ts('Relationship Type'),
      array(
        '' => ts('- select -')) +
      CRM_Contact_BAO_Relationship::getRelationType("Organization"), TRUE
    );

    $searchRows = $this->get('searchRows');
    $searchCount = $this->get('searchCount');
    if ($searchRows) {
      $checkBoxes = array();
      $chekFlag = 0;
      foreach ($searchRows as $id => $row) {
        if (!$chekFlag) {
          $chekFlag = $id;
        }

        $checkBoxes[$id] = $this->createElement('radio', NULL, NULL, NULL, $id);
      }

      $this->addGroup($checkBoxes, 'contact_check');
      if ($chekFlag) {
        $checkBoxes[$chekFlag]->setChecked(TRUE);
      }
      $this->assign('searchRows', $searchRows);
    }


    $this->assign('searchCount', $searchCount);
    $this->assign('searchDone', $this->get('searchDone'));
    $this->assign('contact_type_display', ts('Organization'));
    $this->addElement('submit', $this->getButtonName('refresh'), ts('Search'), array('class' => 'crm-form-submit'));
    $this->addElement('submit', $this->getButtonName('cancel'), ts('Cancel'), array('class' => 'crm-form-submit'));


    $this->addButtons(array(
        array(
          'type' => 'next',
          'name' => ts('Add to Organization'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return void
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->controller->exportValues($this->_name);

    $this->set('searchDone', 0);
    if (!empty($_POST['_qf_AddToOrganization_refresh'])) {
      $searchParams['contact_type'] = array('Organization' => 'Organization');
      $searchParams['rel_contact'] = $params['name'];
      CRM_Contact_Form_Task_AddToHousehold::search($this, $searchParams);
      $this->set('searchDone', 1);
      return;
    }

    $data = array();
    $data['relationship_type_id'] = $params['relationship_type_id'];
    $data['is_active'] = 1;
    $invalid = 0;
    $valid = 0;
    $duplicate = 0;
    if (is_array($this->_contactIds)) {
      foreach ($this->_contactIds as $value) {
        $ids = array();
        $ids['contact'] = $value;
        $errors = CRM_Contact_BAO_Relationship::checkValidRelationship($params, $ids, $params['contact_check']);
        if ($errors) {
          $invalid++;
          continue;
        }

        if (CRM_Contact_BAO_Relationship::checkDuplicateRelationship($params,
            CRM_Utils_Array::value('contact', $ids),
            // step 2
            $params['contact_check']
          )) {
          $duplicate++;
          continue;
        }
        CRM_Contact_BAO_Relationship::add($data, $ids, $params['contact_check']);
        $valid++;
      }
      $org = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $params['contact_check'], 'display_name');
      list($rtype, $a_b) = explode('_', $data['relationship_type_id'], 2);
      $relationship = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_RelationshipType', $rtype, "label_$a_b");

      $status = array(ts('%count %2 %3 relationship created', array('count' => $valid, 'plural' => '%count %2 %3 relationships created', 2 => $relationship, 3 => $org)));
      if ($duplicate) {
        $status[] = ts('%count was skipped because the contact is already %2 %3', array('count' => $duplicate, 'plural' => '%count were skipped because the contacts are already %2 %3', 2 => $relationship, 3 => $org));
      }
      if ($invalid) {
        $status[] = ts('%count relationship was not created because the contact is not of the right type for this relationship', array('count' => $invalid, 'plural' => '%count relationships were not created because the contact is not of the right type for this relationship'));
      }
      $status = '<ul><li>' . implode('</li><li>', $status) . '</li></ul>';
      CRM_Core_Session::setStatus($status, ts('Relationship Created', array('count' => $valid, 'plural' => 'Relationships Created')), 'success', array('expires' => 0));
    }
  }

}
