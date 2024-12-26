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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class helps to print the labels for contacts
 *
 */
class CRM_Member_Form_Task_Label extends CRM_Member_Form_Task {

  /**
   * Build all the data structures needed to build the form.
   *
   * @return void
   */
  public function preProcess(): void {
    parent::preProcess();
    $this->setContactIDs();
    CRM_Core_Resources::singleton()->addScriptFile('civicrm', 'templates/CRM/Member/Form/Task/Label.js');
  }

  /**
   * Build the form object.
   *
   *
   * @return void
   */
  public function buildQuickForm(): void {
    CRM_Contact_Form_Task_Label::buildLabelForm($this);
    $this->addElement('checkbox', 'per_membership', ts('Print one label per Membership (rather than per contact)'));
  }

  /**
   * Set default values for the form.
   *
   * @return array
   *   array of default values
   */
  public function setDefaultValues(): array {
    $defaults = [];
    $format = CRM_Core_BAO_LabelFormat::getDefaultValues();
    $defaults['label_name'] = $format['name'] ?? NULL;
    $defaults['merge_same_address'] = 0;
    $defaults['merge_same_household'] = 0;
    $defaults['do_not_mail'] = 1;
    return $defaults;
  }

  /**
   * Process the form after the input has been submitted and validated.
   *
   *
   * @return void
   */
  public function postProcess(): void {
    $formValues = $this->controller->exportValues($this->_name);
    $locationTypeID = $formValues['location_type_id'];
    $respectDoNotMail = $formValues['do_not_mail'] ?? NULL;
    $labelName = $formValues['label_name'];
    $mergeSameAddress = $formValues['merge_same_address'] ?? NULL;
    $mergeSameHousehold = $formValues['merge_same_household'] ?? NULL;
    $isPerMembership = $formValues['per_membership'] ?? NULL;
    if ($isPerMembership && ($mergeSameAddress || $mergeSameHousehold)) {
      // this shouldn't happen  - perhaps is could if JS is disabled
      CRM_Core_Session::setStatus(ts('As you are printing one label per membership your merge settings are being ignored'));
      $mergeSameAddress = $mergeSameHousehold = FALSE;
    }
    // so no-one is tempted to refer to this again after relevant values are extracted
    unset($formValues);

    [$rows] = CRM_Contact_Form_Task_LabelCommon::getRows($this->_contactIds, $locationTypeID, $respectDoNotMail, $mergeSameAddress, $mergeSameHousehold);

    if ($mergeSameAddress) {
      CRM_Core_BAO_Address::mergeSameAddress($rows);
    }
    if ($mergeSameHousehold) {
      $rows = CRM_Contact_Form_Task_LabelCommon::mergeSameHousehold($rows);
    }
    // format the addresses according to CIVICRM_ADDRESS_FORMAT (CRM-1327)
    foreach ((array) $rows as $id => $row) {
      $row['id'] = $id;
      $formatted = CRM_Utils_Address::formatMailingLabel($row);
      $rows[$id] = [$formatted];
    }
    if ($isPerMembership) {
      $labelRows = [];
      $memberships = civicrm_api3('membership', 'get', [
        'id' => ['IN' => $this->_memberIds],
        'return' => 'contact_id',
        'options' => ['limit' => 0],
      ]);
      foreach ($memberships['values'] as $id => $membership) {
        if (isset($rows[$membership['contact_id']])) {
          $labelRows[$id] = $rows[$membership['contact_id']];
        }
      }
    }
    else {
      $labelRows = $rows;
    }
    //call function to create labels
    CRM_Contact_Form_Task_LabelCommon::createLabel($labelRows, $labelName);
    CRM_Utils_System::civiExit();
  }

}
