<?php

require_once 'CRM/Core/Page.php';

class CRM_Case_Form_AddContact extends CRM_Core_Form {
  public function buildQuickForm() {
    $formBuilder = new CRM_Case_FormBuilder($this);
    $formBuilder->build();
  }

  public function postProcess() {
    $values = $this->controller->exportValues();

    $caseId = (int)$values['assign_to'];
    $roleTypeId = (int)$values['role_type'];
    $contacts = array((int)CRM_Utils_Request::retrieve('cid', 'Positive'));

    $clients = CRM_Case_BAO_Case::getCaseClients($caseId);

    $params = array(
      'contact_id_a' => $clients[0],
      'contact_id_b' => $contacts,
      'case_id' => $caseId,
      'relationship_type_id' => $roleTypeId
    );

    CRM_Contact_BAO_Relationship::createMultiple($params, 'a');

    CRM_Core_Session::setStatus(ts('Contact has been added to case.'), 'Information', 'success');
  }
}
