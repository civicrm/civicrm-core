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
 * This class helps to print the labels for contacts.
 */
class CRM_Contact_Form_Task_Label extends CRM_Contact_Form_Task {
  use CRM_Contact_Form_Task_LabelTrait;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    $this->set('contactIds', $this->_contactIds);
    parent::preProcess();
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    self::buildLabelForm($this);
  }

  /**
   * Common Function to build Mailing Label Form.
   *
   * @param CRM_Core_Form $form
   */
  public static function buildLabelForm($form) {
    $form->setTitle(ts('Make Mailing Labels'));

    //add select for label
    $label = CRM_Core_BAO_LabelFormat::getList(TRUE);

    $form->add('select', 'label_name', ts('Select Label'), ['' => ts('- select label -')] + $label, TRUE);

    // add select for Location Type
    $form->addElement('select', 'location_type_id', ts('Select Location'),
      [
        '' => ts('Primary'),
      ] + CRM_Core_DAO_Address::buildOptions('location_type_id'), TRUE
    );

    // checkbox for SKIP contacts with Do Not Mail privacy option
    $form->addElement('checkbox', 'do_not_mail', ts('Do not print labels for contacts with "Do Not Mail" privacy option checked'));

    $form->add('checkbox', 'merge_same_address', ts('Merge labels for contacts with the same address'), NULL);
    $form->add('checkbox', 'merge_same_household', ts('Merge labels for contacts belonging to the same household'), NULL);

    $form->addButtons([
      [
        'type' => 'submit',
        'name' => ts('Make Mailing Labels'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Done'),
      ],
    ]);
  }

  /**
   * Set default values for the form.
   *
   * @return array
   *   array of default values
   */
  public function setDefaultValues() {
    $defaults = [];
    $format = CRM_Core_BAO_LabelFormat::getDefaultValues();
    $defaults['label_name'] = $format['name'] ?? NULL;
    $defaults['do_not_mail'] = 1;

    return $defaults;
  }

  /**
   * Process the form after the input has been submitted and validated.
   *
   * @throws \CRM_Core_Exception
   */
  public function postProcess(): void {
    $this->createLabels();
    CRM_Utils_System::civiExit();
  }

  /**
   * Check for presence of tokens to be swapped out.
   *
   * @param array $contact
   * @param array $mailingFormatProperties
   * @param array $tokenFields
   *
   * @deprecated since 5.78 will be removed around 5.84
   * @return bool
   */
  public static function tokenIsFound($contact, $mailingFormatProperties, $tokenFields) {
    CRM_Core_Error::deprecatedFunctionWarning('');
    foreach (array_merge($mailingFormatProperties, array_fill_keys($tokenFields, 1)) as $key => $dontCare) {
      //we should not consider addressee for data exists, CRM-6025
      if ($key != 'addressee' && !empty($contact[$key])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * @return array
   */
  protected function getContactIDs(): array {
    return $this->_contactIds;
  }

}
