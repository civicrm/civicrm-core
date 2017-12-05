<?php

class CRM_Case_FormBuilder {
  private $form;

  public function __construct(CRM_Core_Form $form) {
    $this->form = $form;
  }

  public function build() {
    $this->form->add('text', 'assign_to', ts('Assign to'));
    $roleTypes = $this->getRoleTypes();

    $this->form->add(
      'select',
      'role_type',
      ts('Relationship Type'),
      array('' => ts('- select type -')) + $roleTypes,
      FALSE,
      array('class' => 'crm-select2 twenty')
    );

    $this->form->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));
  }

  /**
   * @return array
   */
  private function getRoleTypes() {
    $relType = CRM_Contact_BAO_Relationship::getRelationType('Individual');
    $roleTypes = array();
    foreach ($relType as $k => $v) {
      $roleTypes[substr($k, 0, strpos($k, '_'))] = $v;
    }
    return $roleTypes;
  }

}
