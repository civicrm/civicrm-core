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
 * This class provides the functionality to add contact(s) to Household
 */
class CRM_Contact_Form_Task_AddToHousehold extends CRM_Contact_Form_Task {

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  function preProcess() {
    /*
         * initialize the task and row fields
         */

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

    CRM_Utils_System::setTitle(ts('Add Members to Household'));
    $this->addElement('text', 'name', ts('Find Target Household'));

    $this->add('select', 'relationship_type_id', ts('Relationship Type'),
      array(
        '' => ts('- select -')) +
      CRM_Contact_BAO_Relationship::getRelationType("Household"), TRUE
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
    $this->assign('contact_type_display', ts('Household'));
    $this->addElement('submit', $this->getButtonName('refresh'), ts('Search'), array('class' => 'crm-form-submit'));
    $this->addElement('submit', $this->getButtonName('cancel'), ts('Cancel'), array('class' => 'crm-form-submit'));

    $this->addButtons(array(
        array(
          'type' => 'next',
          'name' => ts('Add to Household'),
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
    if (!empty($_POST['_qf_AddToHousehold_refresh'])) {
      $searchParams['contact_type'] = array('Household' => 'Household');
      $searchParams['rel_contact'] = $params['name'];
      self::search($this, $searchParams);
      $this->set('searchDone', 1);
      return;
    }

    $data = array();
    //$params['relationship_type_id']='4_a_b';
    $data['relationship_type_id'] = $params['relationship_type_id'];
    $data['is_active'] = 1;
    $invalid = $valid = $duplicate = 0;
    if (is_array($this->_contactIds)) {
      foreach ($this->_contactIds as $value) {
        $ids = array();
        $ids['contact'] = $value;
        //contact b --> household
        // contact a  -> individual
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

      $house = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $params['contact_check'], 'display_name');
      list($rtype, $a_b) = explode('_', $data['relationship_type_id'], 2);
      $relationship = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_RelationshipType', $rtype, "label_$a_b");

      $status = array(ts('%count %2 %3 relationship created', array('count' => $valid, 'plural' => '%count %2 %3 relationships created', 2 => $relationship, 3 => $house)));
      if ($duplicate) {
        $status[] = ts('%count was skipped because the contact is already %2 %3', array('count' => $duplicate, 'plural' => '%count were skipped because the contacts are already %2 %3', 2 => $relationship, 3 => $house));
      }
      if ($invalid) {
        $status[] = ts('%count relationship was not created because the contact is not of the right type for this relationship', array('count' => $invalid, 'plural' => '%count relationships were not created because the contact is not of the right type for this relationship'));
      }
      $status = '<ul><li>' . implode('</li><li>', $status) . '</li></ul>';
      CRM_Core_Session::setStatus($status, ts('Relationship Created', array('count' => $valid, 'plural' => 'Relationships Created')), 'success', array('expires' => 0));
    }
  }

  /**
   * This function is to get the result of the search for Add to * forms
   *
   * @param $form
   * @param  array $params This contains elements for search criteria
   *
   * @access public
   *
   * @return void
   */
  function search(&$form, &$params) {
    //max records that will be listed
    $searchValues = array();
    if (!empty($params['rel_contact'])) {
      if (isset($params['rel_contact_id']) &&
        is_numeric($params['rel_contact_id'])
      ) {
        $searchValues[] = array('contact_id', '=', $params['rel_contact_id'], 0, 1);
      }
      else {
        $searchValues[] = array('sort_name', 'LIKE', $params['rel_contact'], 0, 1);
      }
    }
    $contactTypeAdded = FALSE;

    $excludedContactIds = array();
    if (isset($form->_contactId)) {
      $excludedContactIds[] = $form->_contactId;
    }

    if (!empty($params['relationship_type_id'])) {
      $relationshipType = new CRM_Contact_DAO_RelationshipType();
      list($rid, $direction) = explode('_', $params['relationship_type_id'], 2);

      $relationshipType->id = $rid;
      if ($relationshipType->find(TRUE)) {
        if ($direction == 'a_b') {
          $type = $relationshipType->contact_type_b;
          $subType = $relationshipType->contact_sub_type_b;
        }
        else {
          $type = $relationshipType->contact_type_a;
          $subType = $relationshipType->contact_sub_type_a;
        }

        $form->set('contact_type', $type);
        $form->set('contact_sub_type', $subType);
        if ($type == 'Individual' || $type == 'Organization' || $type == 'Household') {
          $searchValues[] = array('contact_type', '=', $type, 0, 0);
          $contactTypeAdded = TRUE;
        }

        if ($subType) {
          $searchValues[] = array('contact_sub_type', '=', $subType, 0, 0);
        }
      }
    }

    if (!$contactTypeAdded && !empty($params['contact_type'])) {
      $searchValues[] = array('contact_type', '=', $params['contact_type'], 0, 0);
    }

    // get the count of contact
    $contactBAO  = new CRM_Contact_BAO_Contact();
    $query       = new CRM_Contact_BAO_Query($searchValues);
    $searchCount = $query->searchQuery(0, 0, NULL, TRUE);
    $form->set('searchCount', $searchCount);
    if ($searchCount <= 50) {
      // get the result of the search
      $result = $query->searchQuery(0, 50, NULL);

      $config = CRM_Core_Config::singleton();
      $searchRows = array();

      //variable is set if only one record is foun and that record already has relationship with the contact
      $duplicateRelationship = 0;

      while ($result->fetch()) {
        $query->convertToPseudoNames($result);
        $contactID = $result->contact_id;
        if (in_array($contactID, $excludedContactIds)) {
          $duplicateRelationship++;
          continue;
        }

        $duplicateRelationship = 0;

        $searchRows[$contactID]['id'] = $contactID;
        $searchRows[$contactID]['name'] = $result->sort_name;
        $searchRows[$contactID]['city'] = $result->city;
        $searchRows[$contactID]['state'] = $result->state_province;
        $searchRows[$contactID]['email'] = $result->email;
        $searchRows[$contactID]['phone'] = $result->phone;

        $contact_type = '<img src="' . $config->resourceBase . 'i/contact_';

        $searchRows[$contactID]['type'] = CRM_Contact_BAO_Contact_Utils::getImage($result->contact_sub_type ?
          $result->contact_sub_type : $result->contact_type
        );
      }

      $form->set('searchRows', $searchRows);
      $form->set('duplicateRelationship', $duplicateRelationship);
    }
    else {
      // resetting the session variables if many records are found
      $form->set('searchRows', NULL);
      $form->set('duplicateRelationship', NULL);
    }
  }
}

