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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * This class provides the functionality for batch entry for contributions/memberships.
 */
class CRM_Batch_Form_Entry extends CRM_Core_Form {

  /**
   * Maximum profile fields that will be displayed.
   */
  protected $_rowCount = 1;

  /**
   * Batch id.
   */
  protected $_batchId;

  /**
   * Batch information.
   */
  protected $_batchInfo = array();

  /**
   * Store the profile id associated with the batch type.
   */
  protected $_profileId;

  public $_action;

  public $_mode;

  public $_params;

  /**
   * When not to reset sort_name.
   */
  protected $_preserveDefault = TRUE;

  /**
   * Contact fields.
   */
  protected $_contactFields = array();

  /**
   * Fields array of fields in the batch profile.
   * (based on the uf_field table data)
   * (this can't be protected as it is passed into the CRM_Contact_Form_Task_Batch::parseStreetAddress function
   * (although a future refactoring might hopefully change that so it uses the api & the function is not
   * required
   * @var array
   */
  public $_fields = array();

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    $this->_batchId = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);

    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse');

    if (empty($this->_batchInfo)) {
      $params = array('id' => $this->_batchId);
      CRM_Batch_BAO_Batch::retrieve($params, $this->_batchInfo);

      $this->assign('batchTotal', !empty($this->_batchInfo['total']) ? $this->_batchInfo['total'] : NULL);
      $this->assign('batchType', $this->_batchInfo['type_id']);

      // get the profile id associted with this batch type
      $this->_profileId = CRM_Batch_BAO_Batch::getProfileId($this->_batchInfo['type_id']);
    }
    CRM_Core_Resources::singleton()
      ->addScriptFile('civicrm', 'templates/CRM/Batch/Form/Entry.js', 1, 'html-header')
      ->addSetting(array('batch' => array('type_id' => $this->_batchInfo['type_id'])))
      ->addSetting(array('setting' => array('monetaryThousandSeparator' => CRM_Core_Config::singleton()->monetaryThousandSeparator)))
      ->addSetting(array('setting' => array('monetaryDecimalPoint' => CRM_Core_Config::singleton()->monetaryDecimalPoint)));

  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    if (!$this->_profileId) {
      CRM_Core_Error::fatal(ts('Profile for bulk data entry is missing.'));
    }

    $this->addElement('hidden', 'batch_id', $this->_batchId);

    $batchTypes = CRM_Core_Pseudoconstant::get('CRM_Batch_DAO_Batch', 'type_id', array('flip' => 1), 'validate');
    // get the profile information
    if ($this->_batchInfo['type_id'] == $batchTypes['Contribution']) {
      CRM_Utils_System::setTitle(ts('Batch Data Entry for Contributions'));
      $customFields = CRM_Core_BAO_CustomField::getFields('Contribution');
    }
    elseif ($this->_batchInfo['type_id'] == $batchTypes['Membership']) {
      CRM_Utils_System::setTitle(ts('Batch Data Entry for Memberships'));
      $customFields = CRM_Core_BAO_CustomField::getFields('Membership');
    }
    elseif ($this->_batchInfo['type_id'] == $batchTypes['Pledge Payment']) {
      CRM_Utils_System::setTitle(ts('Batch Data Entry for Pledge Payments'));
      $customFields = CRM_Core_BAO_CustomField::getFields('Contribution');
    }
    $this->_fields = array();
    $this->_fields = CRM_Core_BAO_UFGroup::getFields($this->_profileId, FALSE, CRM_Core_Action::VIEW);

    // remove file type field and then limit fields
    $suppressFields = FALSE;
    $removehtmlTypes = array('File', 'Autocomplete-Select');
    foreach ($this->_fields as $name => $field) {
      if ($cfID = CRM_Core_BAO_CustomField::getKeyID($name) &&
        in_array($this->_fields[$name]['html_type'], $removehtmlTypes)
      ) {
        $suppressFields = TRUE;
        unset($this->_fields[$name]);
      }

      //fix to reduce size as we are using this field in grid
      if (is_array($field['attributes']) && $this->_fields[$name]['attributes']['size'] > 19) {
        //shrink class to "form-text-medium"
        $this->_fields[$name]['attributes']['size'] = 19;
      }
    }

    $this->addFormRule(array('CRM_Batch_Form_Entry', 'formRule'), $this);

    // add the force save button
    $forceSave = $this->getButtonName('upload', 'force');

    $this->addElement('submit',
      $forceSave,
      ts('Ignore Mismatch & Process the Batch?')
    );

    $this->addButtons(array(
        array(
          'type' => 'upload',
          'name' => ts('Validate & Process the Batch'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Save & Continue Later'),
        ),
      )
    );

    $this->assign('rowCount', $this->_batchInfo['item_count'] + 1);

    $fileFieldExists = FALSE;
    $preserveDefaultsArray = array(
      'first_name',
      'last_name',
      'middle_name',
      'organization_name',
      'household_name',
    );

    $contactTypes = array('Contact', 'Individual', 'Household', 'Organization');
    $contactReturnProperties = array();
    $config = CRM_Core_Config::singleton();

    for ($rowNumber = 1; $rowNumber <= $this->_batchInfo['item_count']; $rowNumber++) {
      $this->addEntityRef("primary_contact_id[{$rowNumber}]", '', array(
          'create' => TRUE,
          'placeholder' => ts('- select -'),
        ));

      // special field specific to membership batch udpate
      if ($this->_batchInfo['type_id'] == 2) {
        $options = array(
          1 => ts('Add Membership'),
          2 => ts('Renew Membership'),
        );
        $this->add('select', "member_option[$rowNumber]", '', $options);
      }
      if ($this->_batchInfo['type_id'] == $batchTypes['Pledge Payment']) {
        $options = array('' => '-select-');
        $optionTypes = array(
          '1' => ts('Adjust Pledge Payment Schedule?'),
          '2' => ts('Adjust Total Pledge Amount?'),
        );
        $this->add('select', "option_type[$rowNumber]", NULL, $optionTypes);
        if (!empty($this->_batchId) && !empty($this->_batchInfo['data']) && !empty($rowNumber)) {
          $dataValues = json_decode($this->_batchInfo['data'], TRUE);
          if (!empty($dataValues['values']['primary_contact_id'][$rowNumber])) {
            $pledgeIDs = CRM_Pledge_BAO_Pledge::getContactPledges($dataValues['values']['primary_contact_id'][$rowNumber]);
            foreach ($pledgeIDs as $pledgeID) {
              $pledgePayment = CRM_Pledge_BAO_PledgePayment::getOldestPledgePayment($pledgeID);
              $options += array($pledgeID => CRM_Utils_Date::customFormat($pledgePayment['schedule_date'], '%m/%d/%Y') . ', ' . $pledgePayment['amount'] . ' ' . $pledgePayment['currency']);
            }
          }
        }

        $this->add('select', "open_pledges[$rowNumber]", '', $options);
      }

      foreach ($this->_fields as $name => $field) {
        if (in_array($field['field_type'], $contactTypes)) {
          $fld = explode('-', $field['name']);
          $contactReturnProperties[$field['name']] = $fld[0];
        }
        CRM_Core_BAO_UFGroup::buildProfile($this, $field, NULL, NULL, FALSE, FALSE, $rowNumber);

        if (in_array($field['name'], $preserveDefaultsArray)) {
          $this->_preserveDefault = FALSE;
        }
      }
    }

    $this->assign('fields', $this->_fields);
    CRM_Core_Resources::singleton()
      ->addSetting(array(
        'contact' => array(
          'return' => implode(',', $contactReturnProperties),
          'fieldmap' => array_flip($contactReturnProperties),
        ),
      ));

    // don't set the status message when form is submitted.
    $buttonName = $this->controller->getButtonName('submit');

    if ($suppressFields && $buttonName != '_qf_Entry_next') {
      CRM_Core_Session::setStatus(ts("File or Autocomplete-Select type field(s) in the selected profile are not supported for Update multiple records."), ts('Some Fields Excluded'), 'info');
    }
  }

  /**
   * Form validations.
   *
   * @param array $params
   *   Posted values of the form.
   * @param array $files
   *   List of errors to be posted back to the form.
   * @param \CRM_Batch_Form_Entry $self
   *   Form object.
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($params, $files, $self) {
    $errors = array();
    $batchTypes = CRM_Core_Pseudoconstant::get('CRM_Batch_DAO_Batch', 'type_id', array('flip' => 1), 'validate');
    $fields = array(
      'total_amount' => ts('Amount'),
      'financial_type' => ts('Financial Type'),
      'payment_instrument' => ts('Payment Method'),
    );

    //CRM-16480 if contact is selected, validate financial type and amount field.
    foreach ($params['field'] as $key => $value) {
      if (isset($value['trxn_id'])) {
        if (0 < CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_contribution WHERE trxn_id = %1', array(1 => array($value['trxn_id'], 'String')))) {
          $errors["field[$key][trxn_id]"] = ts('Transaction ID must be unique within the database');
        }
      }
      foreach ($fields as $field => $label) {
        if (!empty($params['primary_contact_id'][$key]) && empty($value[$field])) {
          $errors["field[$key][$field]"] = ts('%1 is a required field.', array(1 => $label));
        }
      }
    }

    if (!empty($params['_qf_Entry_upload_force'])) {
      if (!empty($errors)) {
        return $errors;
      }
      return TRUE;
    }

    $batchTotal = 0;
    foreach ($params['field'] as $key => $value) {
      $batchTotal += $value['total_amount'];

      //validate for soft credit fields
      if (!empty($params['soft_credit_contact_id'][$key]) && empty($params['soft_credit_amount'][$key])) {
        $errors["soft_credit_amount[$key]"] = ts('Please enter the soft credit amount.');
      }
      if (!empty($params['soft_credit_amount']) && !empty($params['soft_credit_amount'][$key]) && CRM_Utils_Rule::cleanMoney(CRM_Utils_Array::value($key, $params['soft_credit_amount'])) > CRM_Utils_Rule::cleanMoney($value['total_amount'])) {
        $errors["soft_credit_amount[$key]"] = ts('Soft credit amount should not be greater than the total amount');
      }

      //membership type is required for membership batch entry
      if ($self->_batchInfo['type_id'] == $batchTypes['Membership']) {
        if (empty($value['membership_type'][1])) {
          $errors["field[$key][membership_type]"] = ts('Membership type is a required field.');
        }
      }
    }
    if ($self->_batchInfo['type_id'] == $batchTypes['Pledge Payment']) {
      foreach (array_unique($params["open_pledges"]) as $value) {
        if (!empty($value)) {
          $duplicateRows = array_keys($params["open_pledges"], $value);
        }
        if (!empty($duplicateRows) && count($duplicateRows) > 1) {
          foreach ($duplicateRows as $key) {
            $errors["open_pledges[$key]"] = ts('You can not record two payments for the same pledge in a single batch.');
          }
        }
      }
    }
    if ((string) $batchTotal != $self->_batchInfo['total']) {
      $self->assign('batchAmountMismatch', TRUE);
      $errors['_qf_defaults'] = ts('Total for amounts entered below does not match the expected batch total.');
    }

    if (!empty($errors)) {
      return $errors;
    }

    $self->assign('batchAmountMismatch', FALSE);
    return TRUE;
  }

  /**
   * Override default cancel action.
   */
  public function cancelAction() {
    // redirect to batch listing
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/batch', 'reset=1'));
    CRM_Utils_System::civiExit();
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    if (empty($this->_fields)) {
      return;
    }

    // for add mode set smart defaults
    if ($this->_action & CRM_Core_Action::ADD) {
      list($currentDate, $currentTime) = CRM_Utils_Date::setDateDefaults(NULL, 'activityDateTime');

      //get all status
      $allStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
      $completeStatus = array_search('Completed', $allStatus);
      $specialFields = array(
        'join_date' => $currentDate,
        'receive_date' => $currentDate,
        'receive_date_time' => $currentTime,
        'contribution_status_id' => $completeStatus,
      );

      for ($rowNumber = 1; $rowNumber <= $this->_batchInfo['item_count']; $rowNumber++) {
        foreach ($specialFields as $key => $value) {
          $defaults['field'][$rowNumber][$key] = $value;
        }
      }
    }
    else {
      // get the cached info from data column of civicrm_batch
      $data = CRM_Core_DAO::getFieldValue('CRM_Batch_BAO_Batch', $this->_batchId, 'data');
      $defaults = json_decode($data, TRUE);
      $defaults = $defaults['values'];
    }

    return $defaults;
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    $params['actualBatchTotal'] = 0;

    // get the profile information
    $batchTypes = CRM_Core_Pseudoconstant::get('CRM_Batch_DAO_Batch', 'type_id', array('flip' => 1), 'validate');
    if (in_array($this->_batchInfo['type_id'], array($batchTypes['Pledge Payment'], $batchTypes['Contribution']))) {
      $this->processContribution($params);
    }
    elseif ($this->_batchInfo['type_id'] == $batchTypes['Membership']) {
      $this->processMembership($params);
    }

    // update batch to close status
    $paramValues = array(
      'id' => $this->_batchId,
      // close status
      'status_id' => CRM_Core_OptionGroup::getValue('batch_status', 'Closed', 'name'),
      'total' => $params['actualBatchTotal'],
    );

    CRM_Batch_BAO_Batch::create($paramValues);

    // set success status
    CRM_Core_Session::setStatus("", ts("Batch Processed."), "success");

    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/batch', 'reset=1'));
  }

  /**
   * Process contribution records.
   *
   * @param array $params
   *   Associated array of submitted values.
   *
   * @return bool
   */
  private function processContribution(&$params) {
    $dates = array(
      'receive_date',
      'receipt_date',
      'thankyou_date',
      'cancel_date',
    );

    // get the price set associated with offline contribution record.
    $priceSetId = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', 'default_contribution_amount', 'id', 'name');
    $this->_priceSet = current(CRM_Price_BAO_PriceSet::getSetDetail($priceSetId));
    $priceFieldID = CRM_Price_BAO_PriceSet::getOnlyPriceFieldID($this->_priceSet);
    $priceFieldValueID = CRM_Price_BAO_PriceSet::getOnlyPriceFieldValueID($this->_priceSet);

    if (isset($params['field'])) {
      foreach ($params['field'] as $key => $value) {
        // if contact is not selected we should skip the row
        if (empty($params['primary_contact_id'][$key])) {
          continue;
        }

        $value['contact_id'] = CRM_Utils_Array::value($key, $params['primary_contact_id']);

        // update contact information
        $this->updateContactInfo($value);

        //build soft credit params
        if (!empty($params['soft_credit_contact_id'][$key]) && !empty($params['soft_credit_amount'][$key])) {
          $value['soft_credit'][$key]['contact_id'] = $params['soft_credit_contact_id'][$key];
          $value['soft_credit'][$key]['amount'] = CRM_Utils_Rule::cleanMoney($params['soft_credit_amount'][$key]);

          //CRM-15350: if soft-credit-type profile field is disabled or removed then
          //we choose configured SCT default value
          if (!empty($params['soft_credit_type'][$key])) {
            $value['soft_credit'][$key]['soft_credit_type_id'] = $params['soft_credit_type'][$key];
          }
          else {
            $value['soft_credit'][$key]['soft_credit_type_id'] = CRM_Core_OptionGroup::getDefaultValue("soft_credit_type");
          }
        }

        // Build PCP params
        if (!empty($params['pcp_made_through_id'][$key])) {
          $value['pcp']['pcp_made_through_id'] = $params['pcp_made_through_id'][$key];
          $value['pcp']['pcp_display_in_roll'] = !empty($params['pcp_display_in_roll'][$key]);
          if (!empty($params['pcp_roll_nickname'][$key])) {
            $value['pcp']['pcp_roll_nickname'] = $params['pcp_roll_nickname'][$key];
          }
          if (!empty($params['pcp_personal_note'][$key])) {
            $value['pcp']['pcp_personal_note'] = $params['pcp_personal_note'][$key];
          }
        }

        $value['custom'] = CRM_Core_BAO_CustomField::postProcess($value,
          NULL,
          'Contribution'
        );

        foreach ($dates as $val) {
          if (!empty($value[$val])) {
            $value[$val] = CRM_Utils_Date::processDate($value[$val], $value[$val . '_time'], TRUE);
          }
        }

        if (!empty($value['send_receipt'])) {
          $value['receipt_date'] = date('Y-m-d His');
        }
        // these translations & date handling are required because we are calling BAO directly rather than the api
        $fieldTranslations = array(
          'financial_type' => 'financial_type_id',
          'payment_instrument' => 'payment_instrument_id',
          'contribution_source' => 'source',
          'contribution_note' => 'note',

        );
        foreach ($fieldTranslations as $formField => $baoField) {
          if (isset($value[$formField])) {
            $value[$baoField] = $value[$formField];
          }
          unset($value[$formField]);
        }

        $params['actualBatchTotal'] += $value['total_amount'];
        $value['batch_id'] = $this->_batchId;
        $value['skipRecentView'] = TRUE;

        // build line item params
        $this->_priceSet['fields'][$priceFieldID]['options'][$priceFieldValueID]['amount'] = $value['total_amount'];
        $value['price_' . $priceFieldID] = 1;

        $lineItem = array();
        CRM_Price_BAO_PriceSet::processAmount($this->_priceSet['fields'], $value, $lineItem[$priceSetId]);

        // @todo - stop setting amount level in this function & call the CRM_Price_BAO_PriceSet::getAmountLevel
        // function to get correct amount level consistently. Remove setting of the amount level in
        // CRM_Price_BAO_PriceSet::processAmount. Extend the unit tests in CRM_Price_BAO_PriceSetTest
        // to cover all variants.
        unset($value['amount_level']);

        //CRM-11529 for back office transactions
        //when financial_type_id is passed in form, update the
        //line items with the financial type selected in form
        // @todo - create a price set or price field per financial type & simply choose the appropriate
        // price field rather than working around the fact that each price_field is supposed to have a financial
        // type & we are allowing that to be overridden.
        if (!empty($value['financial_type_id']) && !empty($lineItem[$priceSetId])) {
          foreach ($lineItem[$priceSetId] as &$values) {
            $values['financial_type_id'] = $value['financial_type_id'];
          }
        }
        $value['line_item'] = $lineItem;
        //finally call contribution create for all the magic
        $contribution = CRM_Contribute_BAO_Contribution::create($value, CRM_Core_DAO::$_nullArray);
        $batchTypes = CRM_Core_Pseudoconstant::get('CRM_Batch_DAO_Batch', 'type_id', array('flip' => 1), 'validate');
        if (!empty($this->_batchInfo['type_id']) && ($this->_batchInfo['type_id'] == $batchTypes['Pledge Payment'])) {
          $adjustTotalAmount = FALSE;
          if (isset($params['option_type'][$key])) {
            if ($params['option_type'][$key] == 2) {
              $adjustTotalAmount = TRUE;
            }
          }
          $pledgeId = $params['open_pledges'][$key];
          if (is_numeric($pledgeId)) {
            $result = CRM_Pledge_BAO_PledgePayment::getPledgePayments($pledgeId);
            $pledgePaymentId = 0;
            foreach ($result as $key => $values) {
              if ($values['status'] != 'Completed') {
                $pledgePaymentId = $values['id'];
                break;
              }
            }
            CRM_Core_DAO::setFieldValue('CRM_Pledge_DAO_PledgePayment', $pledgePaymentId, 'contribution_id', $contribution->id);
            CRM_Pledge_BAO_PledgePayment::updatePledgePaymentStatus($pledgeId,
              array($pledgePaymentId),
              $contribution->contribution_status_id,
              NULL,
              $contribution->total_amount,
              $adjustTotalAmount
            );
          }
        }

        //process premiums
        if (!empty($value['product_name'])) {
          if ($value['product_name'][0] > 0) {
            list($products, $options) = CRM_Contribute_BAO_Premium::getPremiumProductInfo();

            $value['hidden_Premium'] = 1;
            $value['product_option'] = CRM_Utils_Array::value(
              $value['product_name'][1],
              $options[$value['product_name'][0]]
            );

            $premiumParams = array(
              'product_id' => $value['product_name'][0],
              'contribution_id' => $contribution->id,
              'product_option' => $value['product_option'],
              'quantity' => 1,
            );
            CRM_Contribute_BAO_Contribution::addPremium($premiumParams);
          }
        }
        // end of premium

        //send receipt mail.
        if ($contribution->id && !empty($value['send_receipt'])) {
          // add the domain email id
          $domainEmail = CRM_Core_BAO_Domain::getNameAndEmail();
          $domainEmail = "$domainEmail[0] <$domainEmail[1]>";
          $value['from_email_address'] = $domainEmail;
          $value['contribution_id'] = $contribution->id;
          if (!empty($value['soft_credit'])) {
            $value = array_merge($value, CRM_Contribute_BAO_ContributionSoft::getSoftContribution($contribution->id));
          }
          CRM_Contribute_Form_AdditionalInfo::emailReceipt($this, $value);
        }
      }
    }
    return TRUE;
  }

  /**
   * Process membership records.
   *
   * @param array $params
   *   Associated array of submitted values.
   *
   *
   * @return bool
   */
  private function processMembership(&$params) {
    $dateTypes = array(
      'join_date' => 'joinDate',
      'membership_start_date' => 'startDate',
      'membership_end_date' => 'endDate',
    );

    $dates = array(
      'join_date',
      'start_date',
      'end_date',
      'reminder_date',
    );

    // get the price set associated with offline membership
    $priceSetId = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', 'default_membership_type_amount', 'id', 'name');
    $this->_priceSet = $priceSets = current(CRM_Price_BAO_PriceSet::getSetDetail($priceSetId));

    if (isset($params['field'])) {
      $customFields = array();
      foreach ($params['field'] as $key => $value) {
        // if contact is not selected we should skip the row
        if (empty($params['primary_contact_id'][$key])) {
          continue;
        }

        $value['contact_id'] = CRM_Utils_Array::value($key, $params['primary_contact_id']);

        // update contact information
        $this->updateContactInfo($value);

        $membershipTypeId = $value['membership_type_id'] = $value['membership_type'][1];

        foreach ($dateTypes as $dateField => $dateVariable) {
          $$dateVariable = CRM_Utils_Date::processDate($value[$dateField]);
          $fDate[$dateField] = CRM_Utils_Array::value($dateField, $value);
        }

        $calcDates = array();
        $calcDates[$membershipTypeId] = CRM_Member_BAO_MembershipType::getDatesForMembershipType($membershipTypeId,
          $joinDate, $startDate, $endDate
        );

        foreach ($calcDates as $memType => $calcDate) {
          foreach ($dates as $d) {
            //first give priority to form values then calDates.
            $date = CRM_Utils_Array::value($d, $value);
            if (!$date) {
              $date = CRM_Utils_Array::value($d, $calcDate);
            }

            $value[$d] = CRM_Utils_Date::processDate($date);
          }
        }

        if (!empty($value['send_receipt'])) {
          $value['receipt_date'] = date('Y-m-d His');
        }

        if (!empty($value['membership_source'])) {
          $value['source'] = $value['membership_source'];
        }

        unset($value['membership_source']);

        //Get the membership status
        if (!empty($value['membership_status'])) {
          $value['status_id'] = $value['membership_status'];
          unset($value['membership_status']);
        }

        if (empty($customFields)) {
          // membership type custom data
          $customFields = CRM_Core_BAO_CustomField::getFields('Membership', FALSE, FALSE, $membershipTypeId);

          $customFields = CRM_Utils_Array::crmArrayMerge($customFields,
            CRM_Core_BAO_CustomField::getFields('Membership',
              FALSE, FALSE, NULL, NULL, TRUE
            )
          );
        }

        //check for custom data
        $value['custom'] = CRM_Core_BAO_CustomField::postProcess($params['field'][$key],
          $key,
          'Membership',
          $membershipTypeId
        );

        if (!empty($value['financial_type'])) {
          $value['financial_type_id'] = $value['financial_type'];
        }

        if (!empty($value['payment_instrument'])) {
          $value['payment_instrument_id'] = $value['payment_instrument'];
        }

        // handle soft credit
        if (!empty($params['soft_credit_contact_id'][$key]) && !empty($params['soft_credit_amount'][$key])) {
          $value['soft_credit'][$key]['contact_id'] = $params['soft_credit_contact_id'][$key];
          $value['soft_credit'][$key]['amount'] = CRM_Utils_Rule::cleanMoney($params['soft_credit_amount'][$key]);

          //CRM-15350: if soft-credit-type profile field is disabled or removed then
          //we choose Gift as default value as per Gift Membership rule
          if (!empty($params['soft_credit_type'][$key])) {
            $value['soft_credit'][$key]['soft_credit_type_id'] = $params['soft_credit_type'][$key];
          }
          else {
            $value['soft_credit'][$key]['soft_credit_type_id'] = CRM_Core_OptionGroup::getValue('soft_credit_type', 'Gift', 'name');
          }
        }

        if (!empty($value['receive_date'])) {
          $value['receive_date'] = CRM_Utils_Date::processDate($value['receive_date'], $value['receive_date_time'], TRUE);
        }

        $params['actualBatchTotal'] += $value['total_amount'];

        unset($value['financial_type']);
        unset($value['payment_instrument']);

        $value['batch_id'] = $this->_batchId;
        $value['skipRecentView'] = TRUE;

        // make entry in line item for contribution

        $editedFieldParams = array(
          'price_set_id' => $priceSetId,
          'name' => $value['membership_type'][0],
        );

        $editedResults = array();
        CRM_Price_BAO_PriceField::retrieve($editedFieldParams, $editedResults);

        if (!empty($editedResults)) {
          unset($this->_priceSet['fields']);
          $this->_priceSet['fields'][$editedResults['id']] = $priceSets['fields'][$editedResults['id']];
          unset($this->_priceSet['fields'][$editedResults['id']]['options']);
          $fid = $editedResults['id'];
          $editedFieldParams = array(
            'price_field_id' => $editedResults['id'],
            'membership_type_id' => $value['membership_type_id'],
          );

          $editedResults = array();
          CRM_Price_BAO_PriceFieldValue::retrieve($editedFieldParams, $editedResults);
          $this->_priceSet['fields'][$fid]['options'][$editedResults['id']] = $priceSets['fields'][$fid]['options'][$editedResults['id']];
          if (!empty($value['total_amount'])) {
            $this->_priceSet['fields'][$fid]['options'][$editedResults['id']]['amount'] = $value['total_amount'];
          }

          $fieldID = key($this->_priceSet['fields']);
          $value['price_' . $fieldID] = $editedResults['id'];

          $lineItem = array();
          CRM_Price_BAO_PriceSet::processAmount($this->_priceSet['fields'],
            $value, $lineItem[$priceSetId]
          );

          //CRM-11529 for backoffice transactions
          //when financial_type_id is passed in form, update the
          //lineitems with the financial type selected in form
          if (!empty($value['financial_type_id']) && !empty($lineItem[$priceSetId])) {
            foreach ($lineItem[$priceSetId] as &$values) {
              $values['financial_type_id'] = $value['financial_type_id'];
            }
          }

          $value['lineItems'] = $lineItem;
          $value['processPriceSet'] = TRUE;
        }
        // end of contribution related section

        unset($value['membership_type']);
        unset($value['membership_start_date']);
        unset($value['membership_end_date']);

        $value['is_renew'] = FALSE;
        if (!empty($params['member_option']) && CRM_Utils_Array::value($key, $params['member_option']) == 2) {

          // The following parameter setting may be obsolete.
          $this->_params = $params;
          $value['is_renew'] = TRUE;
          $isPayLater = CRM_Utils_Array::value('is_pay_later', $params);
          $campaignId = NULL;
          if (isset($this->_values) && is_array($this->_values) && !empty($this->_values)) {
            $campaignId = CRM_Utils_Array::value('campaign_id', $this->_params);
            if (!array_key_exists('campaign_id', $this->_params)) {
              $campaignId = CRM_Utils_Array::value('campaign_id', $this->_values);
            }
          }
          foreach (array('join_date', 'start_date', 'end_date') as $dateType) {
            //CRM-18000 - ignore $dateType if its not explicitly passed
            if (!empty($fDate[$dateType]) || !empty($fDate['membership_' . $dateType])) {
              $formDates[$dateType] = CRM_Utils_Array::value($dateType, $value);
            }
          }
          $membershipSource = CRM_Utils_Array::value('source', $value);
          list($membership) = CRM_Member_BAO_Membership::renewMembership(
            $value['contact_id'], $value['membership_type_id'], FALSE,
            //$numTerms should be default to 1.
            NULL, NULL, $value['custom'], 1, NULL, FALSE,
            NULL, $membershipSource, $isPayLater, $campaignId, $formDates
          );

          // make contribution entry
          $contrbutionParams = array_merge($value, array('membership_id' => $membership->id));
          // @todo - calling this from here is pretty hacky since it is called from membership.create anyway
          // This form should set the correct params & not call this fn directly.
          CRM_Member_BAO_Membership::recordMembershipContribution($contrbutionParams);
        }
        else {
          $membership = CRM_Member_BAO_Membership::create($value, CRM_Core_DAO::$_nullArray);
        }

        //process premiums
        if (!empty($value['product_name'])) {
          if ($value['product_name'][0] > 0) {
            list($products, $options) = CRM_Contribute_BAO_Premium::getPremiumProductInfo();

            $value['hidden_Premium'] = 1;
            $value['product_option'] = CRM_Utils_Array::value(
              $value['product_name'][1],
              $options[$value['product_name'][0]]
            );

            $premiumParams = array(
              'product_id' => $value['product_name'][0],
              'contribution_id' => CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipPayment', $membership->id, 'contribution_id', 'membership_id'),
              'product_option' => $value['product_option'],
              'quantity' => 1,
            );
            CRM_Contribute_BAO_Contribution::addPremium($premiumParams);
          }
        }
        // end of premium

        //send receipt mail.
        if ($membership->id && !empty($value['send_receipt'])) {

          // add the domain email id
          $domainEmail = CRM_Core_BAO_Domain::getNameAndEmail();
          $domainEmail = "$domainEmail[0] <$domainEmail[1]>";

          $value['from_email_address'] = $domainEmail;
          $value['membership_id'] = $membership->id;
          $value['contribution_id'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipPayment', $membership->id, 'contribution_id', 'membership_id');
          CRM_Member_Form_Membership::emailReceipt($this, $value, $membership);
        }
      }
    }
    return TRUE;
  }

  /**
   * Update contact information.
   *
   * @param array $value
   *   Associated array of submitted values.
   */
  private function updateContactInfo(&$value) {
    $value['preserveDBName'] = $this->_preserveDefault;

    //parse street address, CRM-7768
    CRM_Contact_Form_Task_Batch::parseStreetAddress($value, $this);

    CRM_Contact_BAO_Contact::createProfileContact($value, $this->_fields,
      $value['contact_id']
    );
  }

  /**
   * Function exists purely for unit testing purposes.
   *
   * If you feel tempted to use this in live code then it probably means there is some functionality
   * that needs to be moved out of the form layer
   *
   * @param array $params
   *
   * @return bool
   */
  public function testProcessMembership($params) {
    return $this->processMembership($params);
  }

  /**
   * Function exists purely for unit testing purposes.
   *
   * If you feel tempted to use this in live code then it probably means there is some functionality
   * that needs to be moved out of the form layer.
   *
   * @param array $params
   *
   * @return bool
   */
  public function testProcessContribution($params) {
    return $this->processContribution($params);
  }

}
