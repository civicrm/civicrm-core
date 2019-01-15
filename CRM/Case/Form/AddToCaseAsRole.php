<?php

/**
 * Class CRM_Case_Form_AddToCaseAsRole
 */
class CRM_Case_Form_AddToCaseAsRole extends CRM_Contact_Form_Task {

  /**
   * @inheritdoc
   */
  public function buildQuickForm() {

    $roleTypes = $this->getRoleTypes();
    $this->add(
      'select',
      'role_type',
      ts('Relationship Type'),
      array('' => ts('- select type -')) + $roleTypes,
      TRUE,
      array('class' => 'crm-select2 twenty')
    );

    $this->addEntityRef(
      'assign_to',
      ts('Assign to'),
      array('entity' => 'case'),
      TRUE
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
  }

  /**
   * Returns list of configured role types for individuals.
   *
   * @return array
   *   List of role types
   */
  private function getRoleTypes() {
    $relType = CRM_Contact_BAO_Relationship::getRelationType('Individual');
    $roleTypes = array();

    foreach ($relType as $k => $v) {
      $roleTypes[substr($k, 0, strpos($k, '_'))] = $v;
    }
    return $roleTypes;
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
    $caseRole = CRM_Case_BAO_Case::getCaseRoleDirection($caseId, $roleTypeId);

    $params = array(
      'case_id' => $caseId,
      'relationship_type_id' => $roleTypeId,
    );

    if ($caseRole[$roleTypeId]['direction'] == 'b_a') {
      $params['contact_id_b'] = $clients[0];
      $params['contact_id_a'] = $contacts;
      CRM_Contact_BAO_Relationship::createMultiple($params, 'b');
    }
    elseif ($caseRole[$roleTypeId]['direction'] == 'a_b'  || $caseRole[$roleTypeId]['direction'] = 'bidirectional') {
      $params['contact_id_a'] = $clients[0];
      $params['contact_id_b'] = $contacts;
      CRM_Contact_BAO_Relationship::createMultiple($params, 'a');
    }




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
