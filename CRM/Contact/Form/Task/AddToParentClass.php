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
 * This class provides the shared functionality for addToHousehold and addToOrganization.
 */
class CRM_Contact_Form_Task_AddToParentClass extends CRM_Contact_Form_Task {

  /**
   * Exported parameters from the form.
   *
   * @var array
   */
  protected $params;

  /**
   * Build the form object.
   */
  public function preProcess() {
    parent::preProcess();
  }

  public function buildQuickForm() {
    $contactType = $this->get('contactType');
    $this->setTitle(ts('Add Contacts to %1', [1 => $contactType]));
    $this->addElement('text', 'name', ts('Find Target %1', [1 => $contactType]));

    $this->add('select',
      'relationship_type_id',
      ts('Relationship Type'),
      [
        '' => ts('- select -'),
      ] +
      CRM_Contact_BAO_Relationship::getRelationType($contactType), TRUE
    );

    $searchRows = $this->get('searchRows');
    $searchCount = $this->get('searchCount');
    $this->assign('searchRows', FALSE);
    if ($searchRows) {
      $checkBoxes = [];
      $chekFlag = 0;
      foreach ($searchRows as $id => $row) {
        if (!$chekFlag) {
          $chekFlag = $id;
        }

        $checkBoxes[$id] = NULL;
      }

      $group = $this->addRadio('contact_check', NULL, $checkBoxes);
      $groupElements = $group->getElements();
      if ($chekFlag) {
        foreach ($groupElements as $groupElement) {
          if ($groupElement->getValue() == $chekFlag) {
            $groupElement->setChecked(TRUE);
          }
        }
      }
      $this->assign('searchRows', $searchRows);
    }

    $this->assign('searchCount', $searchCount);
    $this->assign('searchDone', $this->get('searchDone'));
    $this->assign('contact_type_display', $contactType);
    $buttonAttrs = [
      'type' => 'submit',
      'class' => 'crm-form-submit',
      'value' => 1,
    ];
    $this->addElement('xbutton', $this->getButtonName('refresh'), ts('Search'), $buttonAttrs);
    $this->addElement('xbutton', $this->getButtonName('cancel'), ts('Cancel'), $buttonAttrs);
    $this->addButtons([
      [
        'type' => 'next',
        'name' => ts('Add to %1', [1 => $contactType]),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);
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
    $params = [
      'relationship_type_id' => $relationshipTypeParts[0],
      'is_active' => 1,
    ];
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

    $status = [
      ts('%count %2 %3 relationship created', [
        'count' => $outcome['valid'],
        'plural' => '%count %2 %3 relationships created',
        2 => $relationshipLabel,
        3 => $relatedContactName,
      ]),
    ];
    if ($outcome['duplicate']) {
      $status[] = ts('%count was skipped because the contact is already %2 %3', [
        'count' => $outcome['duplicate'],
        'plural' => '%count were skipped because the contacts are already %2 %3',
        2 => $relationshipLabel,
        3 => $relatedContactName,
      ]);
    }
    if ($outcome['invalid']) {
      $status[] = ts('%count relationship was not created because the contact is not of the right type for this relationship', [
        'count' => $outcome['invalid'],
        'plural' => '%count relationships were not created because the contact is not of the right type for this relationship',
      ]);
    }
    $status = '<ul><li>' . implode('</li><li>', $status) . '</li></ul>';
    CRM_Core_Session::setStatus($status, ts('Relationship created.', [
      'count' => $outcome['valid'],
      'plural' => 'Relationships created.',
    ]), 'success');

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
    $searchValues = [];
    if (!empty($params['rel_contact'])) {
      if (isset($params['rel_contact_id']) &&
        is_numeric($params['rel_contact_id'])
      ) {
        $searchValues[] = ['contact_id', '=', $params['rel_contact_id'], 0, 1];
      }
      else {
        $searchValues[] = ['sort_name', 'LIKE', $params['rel_contact'], 0, 1];
      }
    }
    $contactTypeAdded = FALSE;

    $excludedContactIds = [];
    if (isset($form->_contactId)) {
      $excludedContactIds[] = $form->_contactId;
    }

    if (!empty($params['relationship_type_id'])) {
      $relationshipType = new CRM_Contact_DAO_RelationshipType();
      [$rid, $direction] = explode('_', $params['relationship_type_id'], 2);

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
          $searchValues[] = ['contact_type', '=', $type, 0, 0];
          $contactTypeAdded = TRUE;
        }

        if ($subType) {
          $searchValues[] = ['contact_sub_type', '=', $subType, 0, 0];
        }
      }
    }

    if (!$contactTypeAdded && !empty($params['contact_type'])) {
      $searchValues[] = ['contact_type', '=', $params['contact_type'], 0, 0];
    }

    // get the count of contact
    $query = new CRM_Contact_BAO_Query($searchValues);
    $searchCount = $query->searchQuery(0, 0, NULL, TRUE);
    $form->set('searchCount', $searchCount);
    if ($searchCount <= 50) {
      // get the result of the search
      $result = $query->searchQuery(0, 50, NULL);

      $config = CRM_Core_Config::singleton();
      $searchRows = [];

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

        $searchRows[$contactID]['type'] = CRM_Contact_BAO_Contact_Utils::getImage($result->contact_sub_type ?: $result->contact_type
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

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    // store the submitted values in an array
    $this->params = $this->controller->exportValues($this->_name);
    $this->set('searchDone', 0);
    $contactType = $this->get('contactType');

    if (!empty($_POST["_qf_AddTo{$contactType}_refresh"])) {
      $searchParams['contact_type'] = $contactType;
      $searchParams['rel_contact'] = $this->params['name'];
      $this->search($this, $searchParams);
      $this->set('searchDone', 1);
      return;
    }
    $this->addRelationships();
  }

}
