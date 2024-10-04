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
   */
  public function postProcess() {
    $fv = $this->controller->exportValues($this->_name);
    $locName = NULL;

    $addressReturnProperties = CRM_Contact_Form_Task_LabelCommon::getAddressReturnProperties();

    //build the returnproperties
    $returnProperties = ['display_name' => 1, 'contact_type' => 1, 'prefix_id' => 1];
    $mailingFormat = Civi::settings()->get('mailing_format');

    $mailingFormatProperties = [];
    if ($mailingFormat) {
      $mailingFormatProperties = CRM_Utils_Token::getReturnProperties($mailingFormat);
      $returnProperties = array_merge($returnProperties, $mailingFormatProperties);
    }
    //we should not consider addressee for data exists, CRM-6025
    if (array_key_exists('addressee', $mailingFormatProperties)) {
      unset($mailingFormatProperties['addressee']);
    }

    if (isset($fv['merge_same_address'])) {
      // we need first name/last name for summarising to avoid spillage
      $returnProperties['first_name'] = 1;
      $returnProperties['last_name'] = 1;
    }

    /*
     * CRM-8338: replace ids of household members with the id of their household
     * so we can merge labels by household.
     */
    if (isset($fv['merge_same_household'])) {
      $this->mergeContactIdsByHousehold();
    }

    //get the contacts information
    $params = [];
    if (!empty($fv['location_type_id'])) {
      $locType = CRM_Core_DAO_Address::buildOptions('location_type_id');
      $locName = $locType[$fv['location_type_id']];
      $location = ['location' => ["{$locName}" => $addressReturnProperties]];
      $returnProperties = array_merge($returnProperties, $location);
      $params[] = ['location_type', '=', [1 => $fv['location_type_id']], 0, 0];
      $primaryLocationOnly = FALSE;
    }
    else {
      $returnProperties = array_merge($returnProperties, $addressReturnProperties);
      $primaryLocationOnly = TRUE;
    }

    $rows = [];
    foreach ($this->_contactIds as $key => $contactID) {
      $params[] = [
        CRM_Core_Form::CB_PREFIX . $contactID,
        '=',
        1,
        0,
        0,
      ];
    }

    // fix for CRM-2651
    if (!empty($fv['do_not_mail'])) {
      $params[] = ['do_not_mail', '=', 0, 0, 0];
    }
    // fix for CRM-2613
    $params[] = ['is_deceased', '=', 0, 0, 0];

    //get the total number of contacts to fetch from database.
    $numberofContacts = count($this->_contactIds);
    [$details] = CRM_Contact_BAO_Query::apiQuery($params, $returnProperties, NULL, NULL, 0, $numberofContacts, TRUE, FALSE, TRUE, CRM_Contact_BAO_Query::MODE_CONTACTS, NULL, $primaryLocationOnly);

    foreach ($this->_contactIds as $value) {
      $contact = $details[$value] ?? NULL;

      // we need to remove all the "_id"
      unset($contact['contact_id']);

      if ($locName && !empty($contact[$locName])) {
        // If location type is not primary, $contact contains
        // one more array as "$contact[$locName] = array( values... )"

        $contact = array_merge($contact, $contact[$locName]);
        unset($contact[$locName]);

        if (!empty($contact['county_id'])) {
          unset($contact['county_id']);
        }
      }
      else {

        if (!empty($contact['addressee_display'])) {
          $contact['addressee_display'] = trim($contact['addressee_display']);
        }
        if (!empty($contact['addressee'])) {
          $contact['addressee'] = $contact['addressee_display'];
        }
      }

      // now create the rows for generating mailing labels
      foreach ($contact as $field => $fieldValue) {
        $rows[$value][$field] = $fieldValue;
      }
    }

    if (isset($fv['merge_same_address'])) {
      CRM_Core_BAO_Address::mergeSameAddress($rows);
    }

    // format the addresses according to CIVICRM_ADDRESS_FORMAT (CRM-1327)
    foreach ($rows as $id => $row) {
      $row['id'] = $id;
      $formatted = CRM_Utils_Address::formatMailingLabel($row);
      $rows[$id] = [$formatted];
    }

    //call function to create labels
    $this->createLabel($rows, $fv['label_name']);
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
   * Create labels (pdf).
   *
   * @param array $contactRows
   *   Associated array of contact data.
   * @param string $format
   *   Format in which labels needs to be printed.
   */
  private function createLabel(array $contactRows, $format) {
    $pdf = new CRM_Utils_PDF_Label($format, 'mm');
    $pdf->Open();
    $pdf->AddPage();

    //build contact string that needs to be printed
    $val = NULL;
    foreach ($contactRows as $value) {
      foreach ($value as $v) {
        $val .= "$v\n";
      }

      $pdf->AddPdfLabel($val);
      $val = '';
    }
    if (CIVICRM_UF === 'UnitTests') {
      throw new CRM_Core_Exception_PrematureExitException('pdf output called', ['contactRows' => $contactRows, 'format' => $format, 'pdf' => $pdf]);
    }
    $pdf->Output('MailingLabels_CiviCRM.pdf', 'D');
  }

}
