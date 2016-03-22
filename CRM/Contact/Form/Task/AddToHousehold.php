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
 * This class provides the functionality to add contact(s) to Household.
 */
class CRM_Contact_Form_Task_AddToHousehold extends CRM_Contact_Form_Task_AddToParentClass {

  /**
   * Build the form object.
   */
  public function buildQuickForm() {

    CRM_Utils_System::setTitle(ts('Add Members to Household'));
    $this->addElement('text', 'name', ts('Find Target Household'));

    $this->add('select', 'relationship_type_id', ts('Relationship Type'),
      array(
        '' => ts('- select -'),
      ) +
      CRM_Contact_BAO_Relationship::getRelationType("Household"), TRUE
    );

    $searchRows = $this->get('searchRows');
    $searchCount = $this->get('searchCount');
    if ($searchRows) {
      $checkBoxes = array();
      $checkFlag = 0;
      foreach ($searchRows as $id => $row) {
        if (!$checkFlag) {
          $checkFlag = $id;
        }
        $checkBoxes[$id] = $this->createElement('radio', NULL, NULL, NULL, $id);
      }
      $this->addGroup($checkBoxes, 'contact_check');
      if ($checkFlag) {
        $checkBoxes[$checkFlag]->setChecked(TRUE);
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
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {

    // store the submitted values in an array
    $this->params = $this->controller->exportValues($this->_name);

    $this->set('searchDone', 0);
    if (!empty($_POST['_qf_AddToHousehold_refresh'])) {
      $searchParams['contact_type'] = 'Household';
      $searchParams['rel_contact'] = $this->params['name'];
      $this->search($this, $searchParams);
      $this->set('searchDone', 1);
      return;
    }

    $this->addRelationships();
  }

}
