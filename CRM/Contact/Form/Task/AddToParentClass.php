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
 * This class provides the shared functionality for addToHousehold and addToOrganization.
 */
class CRM_Contact_Form_Task_AddToParentClass extends CRM_Contact_Form_Task {

  /**
   * Exported parameters from the form.
   *
   * @var array.
   */
  protected $params;

  /**
   * Build the form object.
   */
  public function preProcess() {
    parent::preProcess();
  }

  /**
   * Add relationships from form.
   */
  public function addRelationships() {

    if (!is_array($this->_contactIds)) {
      // Could this really happen?
      return;
    }
    $relationshipTypeParts = explode('_', $this->params['relationship_type_id']);
    $params = array(
      'relationship_type_id' => $relationshipTypeParts[0],
      'is_active' => 1,
    );
    $secondaryRelationshipSide = $relationshipTypeParts[1];
    $primaryRelationshipSide = $relationshipTypeParts[2];
    $primaryFieldName = 'contact_id_' . $primaryRelationshipSide;
    $secondaryFieldName = 'contact_id_' . $secondaryRelationshipSide;

    $relationshipLabel = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_RelationshipType',
      $params['relationship_type_id'], "label_{$secondaryRelationshipSide}_{$primaryRelationshipSide}");

    $params[$secondaryFieldName] = $this->_contactIds;
    $params[$primaryFieldName] = $this->params['contact_check'];
    $outcome = CRM_Contact_BAO_Relationship::createMultiple($params, $primaryRelationshipSide);

    $relatedContactName = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $params[$primaryFieldName],
      'display_name');

    $status = array(
      ts('%count %2 %3 relationship created', array(
        'count' => $outcome['valid'],
        'plural' => '%count %2 %3 relationships created',
        2 => $relationshipLabel,
        3 => $relatedContactName,
      )),
    );
    if ($outcome['duplicate']) {
      $status[] = ts('%count was skipped because the contact is already %2 %3', array(
        'count' => $outcome['duplicate'],
        'plural' => '%count were skipped because the contacts are already %2 %3',
        2 => $relationshipLabel,
        3 => $relatedContactName,
      ));
    }
    if ($outcome['invalid']) {
      $status[] = ts('%count relationship was not created because the contact is not of the right type for this relationship', array(
        'count' => $outcome['invalid'],
        'plural' => '%count relationships were not created because the contact is not of the right type for this relationship',
      ));
    }
    $status = '<ul><li>' . implode('</li><li>', $status) . '</li></ul>';
    CRM_Core_Session::setStatus($status, ts('Relationship created.', array(
      'count' => $outcome['valid'],
      'plural' => 'Relationships created.',
    )), 'success', array('expires' => 0));

  }

  /**
   * Get the result of the search for Add to * forms.
   *
   * @param CRM_Core_Form $form
   * @param array $params
   *   This contains elements for search criteria.
   */
  public function search(&$form, &$params) {
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
    $contactBAO = new CRM_Contact_BAO_Contact();
    $query = new CRM_Contact_BAO_Query($searchValues);
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

        $searchRows[$contactID]['type'] = CRM_Contact_BAO_Contact_Utils::getImage($result->contact_sub_type ? $result->contact_sub_type : $result->contact_type
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
