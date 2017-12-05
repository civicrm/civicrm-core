<?php

/**
 * Class CRM_Case_Form_AddToCaseAsRole
 */
class CRM_Case_Form_AddToCaseAsRole extends CRM_Contact_Form_Task {

  /**
   * @inheritdoc
   */
  public function buildQuickForm() {

    $this->add('text', 'assign_to', ts('Assign to'));
    $roleTypes = $this->getRoleTypes();

    $this->add(
      'select',
      'role_type',
      ts('Relationship Type'),
      array('' => ts('- select type -')) + $roleTypes,
      FALSE,
      array('class' => 'crm-select2 twenty')
    );

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
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

    $params = array(
      'contact_id_a' => $clients[0],
      'contact_id_b' => $contacts,
      'case_id' => $caseId,
      'relationship_type_id' => $roleTypeId,
    );

    CRM_Contact_BAO_Relationship::createMultiple($params, 'a');

    $url = CRM_Utils_System::url(
      'civicrm/contact/view/case',
      sprintf('cid=%d&id=%d', $clients[0], $caseId)
    );
    CRM_Utils_System::redirect($url);
  }

}
