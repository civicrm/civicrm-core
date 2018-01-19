<?php

/**
 * Class CRM_Case_Form_AddToCaseAsRole
 */
class CRM_Case_Form_AddToCaseAsRole extends CRM_Contact_Form_Task {

  /**
   * @inheritdoc
   */
  public function buildQuickForm() {

    $this->addEntityRef(
      'assign_to',
      ts('Assign to'),
      array('entity' => 'case'),
      TRUE
    );

    $roleTypes = $this->getRoleTypes();
    $this->add(
      'select',
      'role_type',
      ts('Relationship Type'),
      array('' => ts('- select type -')) + $roleTypes,
      TRUE,
      array('class' => 'crm-select2 twenty')
    );

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ),
    ));

    $this->assign('typeFilter', $this->getSelectedContactTypesFilter());
  }

  /**
   * Returns list of configured role types for individuals.
   *
   * @return array
   *   List of role types
   */
  private function getRoleTypes() {
    $relationshipType = array();
    $allRelationshipType = CRM_Core_PseudoConstant::relationshipType();

    foreach ($allRelationshipType as $key => $type) {
      $relationshipType[$key] = $type['label_a_b'];
    }

    return $relationshipType;
  }

  /**
   * Calculates the filter to be used to filter available case roles according
   * to selected contacts' types.
   *
   * - If all contacts have the same type, case roles should only allow that
   *   contact type or all contact types.
   * - If more than one contact type was selected, only case roles that allow
   *   all contact types should be shown.
   *
   * @return array
   */
  private function getSelectedContactTypesFilter() {
    $result = civicrm_api3('Contact', 'get', array(
      'sequential' => 1,
      'return' => array('contact_type'),
      'id' => array('IN' => $this->_contactIds),
    ));

    $contactTypes = array();
    foreach ($result['values'] as $contact) {
      $contactTypes[$contact['contact_type']] = $contact['contact_type'];
    }

    if (count($contactTypes) === 1) {
      $filter = array_shift($contactTypes);
    }
    else {
      $filter = '';
    }

    return $filter;
  }

  /**
   * @inheritdoc
   */
  public function postProcess() {
    $values = $this->controller->exportValues();

    $caseId = (int) $values['assign_to'];
    $roleTypeId = (int) $values['role_type'];
    $contacts = $this->_contactIds;

    $clients = CRM_Case_BAO_Case::getCaseClients($caseId);

    $params = array(
      'contact_id_a' => $clients[0],
      'contact_id_b' => $contacts,
      'case_id' => $caseId,
      'relationship_type_id' => $roleTypeId,
    );

    CRM_Contact_BAO_Relationship::createMultiple($params, 'a');

    $url = CRM_Utils_System::url(
      'civicrm/contact/view/case',
      array(
        'cid' => $clients[0],
        'id' => $caseId,
        'reset' => 1,
        'action' => 'view',
      )
    );
    CRM_Utils_System::redirect($url);
  }

}
