<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Orgtaskskr_Form_OrgTaskListAdmin extends CRM_Core_Form {
  /**
   * Builds Form!
   */
  public function buildQuickForm() {

    // Relationship Types Multi-select
    $this->add(
      'select', // field type
      'included_relationships', // field name
      'Included Relationship Types', // field label
      $this->getRelationshipTypes(), // list of options
      TRUE, // is required
      array('class' => 'crm-select2', 'multiple' => true)
    );
    
    // Activity Types Multi-select
    $this->add(
      'select', // field type
      'included_activities', // field name
      'Included Activity Types', // field label
      $this->getActivityTypes(), // list of options
      TRUE, // is required
      array('class' => 'crm-select2', 'multiple' => true)
    );
    
    // Submit Buttons
    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }
  
  /**
   * Calls API to get all possible relationship types between two contacts.
   * @return array
   */
  function getRelationshipTypes() {
    $values = array();
    
    $result = civicrm_api3('RelationshipType', 'get', array(
      'sequential' => 1,
    ));
    
    foreach ($result['values'] as $type) {
      $values[$type['id']] = $type['label_b_a'] . ' / ' . $type['label_a_b'];
    }
    
    return $values;
  }
  
  /**
   * Method that retrieves values of all activity types.
   * @return array
   */
  private function getActivityTypes() {
    $values = array();
    
    $result = civicrm_api3('OptionGroup', 'get', array(
      'sequential' => 1,
      'return' => array("name"),
      'name' => 'activity_type',
      'api.OptionValue.get' => array('option_group_id' => "\$value.id"),
    ));
    
    foreach ($result['values'] as $currGroup) {
      foreach ($currGroup['api.OptionValue.get']['values'] as $currOptValue) {
        $values[$currOptValue['value']] = $currOptValue['label'];
      }
    }
    
    return $values;
  }
  
  /**
   * Inserts values into settings table
   */
  public function postProcess() {
    $values = $this->exportValues();
    
    Civi::settings()->set('orgtasks_included_relationships', $values['included_relationships']);
    Civi::settings()->set('orgtasks_included_activities', $values['included_activities']);
    
    parent::postProcess();
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }
}
