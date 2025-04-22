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

use Civi\Api4\Contribution;

/**
 * This class provides the functionality for batch entry for contributions/memberships.
 */
class CRM_Batch_Form_Entry extends CRM_Core_Form {

  /**
   * Batch id.
   *
   * @var int
   */
  protected $_batchId;

  /**
   * Batch information.
   *
   * @var array
   */
  protected $_batchInfo = [];

  /**
   * Store the profile id associated with the batch type.
   * @var int
   */
  protected $_profileId;

  public $_action;

  public $_mode;

  public $_params;

  /**
   * When not to reset sort_name.
   *
   * @var bool
   */
  protected $_preserveDefault = TRUE;

  /**
   * Renew option for current row.
   *
   * This is as set on the form.
   *
   * @var int
   */
  protected $currentRowIsRenewOption;

  /**
   * Fields array of fields in the batch profile.
   *
   * (based on the uf_field table data)
   * (this can't be protected as it is passed into the CRM_Contact_Form_Task_Batch::parseStreetAddress function
   * (although a future refactoring might hopefully change that so it uses the api & the function is not
   * required
   *
   * @var array
   */
  public $_fields = [];

  /**
   * @var int
   */
  protected $currentRowContributionID;

  /**
   * Row being processed.
   *
   * @var array
   */
  protected $currentRow = [];

  /**
   * @var array
   */
  protected $currentRowExistingMembership;

  /**
   * Get the contribution id for the current row.
   *
   * @return int
   * @throws \CRM_Core_Exception
   */
  public function getCurrentRowContributionID(): int {
    if (!isset($this->currentRowContributionID)) {
      $this->currentRowContributionID = CRM_Member_BAO_MembershipPayment::getLatestContributionIDFromLineitemAndFallbackToMembershipPayment($this->getCurrentRowMembershipID());
    }
    return $this->currentRowContributionID;
  }

  /**
   * Set the contribution ID for the current row.
   *
   * @param int $currentRowContributionID
   */
  public function setCurrentRowContributionID(int $currentRowContributionID): void {
    $this->currentRowContributionID = $currentRowContributionID;
  }

  /**
   * @return int
   */
  public function getCurrentRowMembershipID() {
    return $this->currentRowMembershipID;
  }

  /**
   * Get the order (contribution) status for the current row.
   */
  protected function getCurrentRowPaymentStatus() {
    return CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $this->currentRow['contribution_status_id']);
  }

  /**
   * Get the contact ID for the current row.
   *
   * @return int
   */
  public function getCurrentRowContactID(): int {
    return $this->currentRow['contact_id'];
  }

  /**
   * Get the membership type ID for the current row.
   *
   * @return int
   */
  public function getCurrentRowMembershipTypeID(): int {
    return $this->currentRow['membership_type_id'];
  }

  /**
   * Set the membership id for the current row.
   *
   * @param int $currentRowMembershipID
   */
  public function setCurrentRowMembershipID(int $currentRowMembershipID): void {
    $this->currentRowMembershipID = $currentRowMembershipID;
  }

  /**
   * @var int
   */
  protected $currentRowMembershipID;

  /**
   * Monetary fields that may be submitted.
   *
   * These should get a standardised format in the beginPostProcess function.
   *
   * These fields are common to many forms. Some may override this.
   * @var array
   */
  protected $submittableMoneyFields = ['total_amount', 'net_amount', 'non_deductible_amount', 'fee_amount'];

  /**
   * Build all the data structures needed to build the form.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    $this->_batchId = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);

    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse');

    if (empty($this->_batchInfo)) {
      $params = ['id' => $this->_batchId];
      CRM_Batch_BAO_Batch::retrieve($params, $this->_batchInfo);

      $this->assign('batchTotal', !empty($this->_batchInfo['total']) ? $this->_batchInfo['total'] : NULL);
      $this->assign('batchType', $this->_batchInfo['type_id']);

      // get the profile id associated with this batch type
      $this->_profileId = CRM_Batch_BAO_Batch::getProfileId($this->_batchInfo['type_id']);
    }
    CRM_Core_Resources::singleton()
      ->addScriptFile('civicrm', 'templates/CRM/Batch/Form/Entry.js', 1, 'html-header')
      ->addSetting(['batch' => ['type_id' => $this->_batchInfo['type_id']]])
      ->addSetting(['setting' => ['monetaryThousandSeparator' => CRM_Core_Config::singleton()->monetaryThousandSeparator]])
      ->addSetting(['setting' => ['monetaryDecimalPoint' => CRM_Core_Config::singleton()->monetaryDecimalPoint]]);

    $this->assign('defaultCurrencySymbol', CRM_Core_BAO_Country::defaultCurrencySymbol());
    // This could be updated to TRUE in the formRule
    $this->addExpectedSmartyVariable('batchAmountMismatch');
    // It is unclear where this is otherwise assigned but the template expects it.
    $this->addExpectedSmartyVariable('contactFields');
    // The not-always-present refresh button.
    $this->addOptionalQuickFormElement('_qf_Batch_refresh');
  }

  /**
   * Set Batch ID.
   *
   * @param int $id
   */
  public function setBatchID($id) {
    $this->_batchId = $id;
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(): void {
    if (!$this->_profileId) {
      CRM_Core_Error::statusBounce(ts('Profile for bulk data entry is missing.'));
    }

    $this->addElement('hidden', 'batch_id', $this->_batchId);

    $batchTypes = array_flip(CRM_Batch_DAO_Batch::buildOptions('type_id', 'validate'));
    // get the profile information
    if ($this->_batchInfo['type_id'] == $batchTypes['Contribution']) {
      $this->setTitle(ts('Batch Data Entry for Contributions'));
    }
    elseif ($this->_batchInfo['type_id'] == $batchTypes['Membership']) {
      $this->setTitle(ts('Batch Data Entry for Memberships'));
    }
    elseif ($this->_batchInfo['type_id'] == $batchTypes['Pledge Payment']) {
      $this->setTitle(ts('Batch Data Entry for Pledge Payments'));
    }

    $this->_fields = CRM_Core_BAO_UFGroup::getFields($this->_profileId, FALSE, CRM_Core_Action::VIEW);

    foreach ($this->_fields as $name => $field) {
      //fix to reduce size as we are using this field in grid
      if (is_array($field['attributes']) && ($this->_fields[$name]['attributes']['size'] ?? 0) > 19) {
        //shrink class to "form-text-medium"
        $this->_fields[$name]['attributes']['size'] = 19;
      }
    }

    $this->addFormRule(['CRM_Batch_Form_Entry', 'formRule'], $this);

    // add the force save button
    $forceSave = $this->getButtonName('upload', 'force');

    $this->addElement('xbutton',
      $forceSave,
      ts('Ignore Mismatch & Process the Batch?'),
      [
        'type' => 'submit',
        'value' => 1,
        'class' => 'crm-button crm-button_qf_Entry_upload_force-save',
      ]
    );

    $this->addButtons([
      [
        'type' => 'upload',
        'name' => ts('Validate & Process the Batch'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Save & Continue Later'),
      ],
    ]);

    $this->assign('rowCount', $this->_batchInfo['item_count'] + 1);

    $preserveDefaultsArray = [
      'first_name',
      'last_name',
      'middle_name',
      'organization_name',
      'household_name',
    ];

    $contactTypes = array_merge(['Contact'], CRM_Contact_BAO_ContactType::basicTypes(TRUE));
    $contactReturnProperties = [];

    for ($rowNumber = 1; $rowNumber <= $this->_batchInfo['item_count']; $rowNumber++) {
      $this->addEntityRef("primary_contact_id[{$rowNumber}]", '', [
        'create' => TRUE,
        'placeholder' => ts('- select -'),
      ]);

      // special field specific to membership batch update
      if ($this->_batchInfo['type_id'] == 2) {
        $options = [
          1 => ts('Add Membership'),
          2 => ts('Renew Membership'),
        ];
        $this->add('select', "member_option[$rowNumber]", '', $options);
      }
      if ($this->_batchInfo['type_id'] == $batchTypes['Pledge Payment']) {
        $options = ['' => ts('-select-')];
        $optionTypes = [
          '1' => ts('Adjust Pledge Payment Schedule?'),
          '2' => ts('Adjust Total Pledge Amount?'),
        ];
        $this->add('select', "option_type[$rowNumber]", NULL, $optionTypes);
        if (!empty($this->_batchId) && !empty($this->_batchInfo['data']) && !empty($rowNumber)) {
          $dataValues = json_decode($this->_batchInfo['data'], TRUE);
          if (!empty($dataValues['values']['primary_contact_id'][$rowNumber])) {
            $pledgeIDs = CRM_Pledge_BAO_Pledge::getContactPledges($dataValues['values']['primary_contact_id'][$rowNumber]);
            foreach ($pledgeIDs as $pledgeID) {
              $pledgePayment = CRM_Pledge_BAO_PledgePayment::getOldestPledgePayment($pledgeID);
              $options += [$pledgeID => CRM_Utils_Date::customFormat($pledgePayment['schedule_date'], '%m/%d/%Y') . ', ' . $pledgePayment['amount'] . ' ' . $pledgePayment['currency']];
            }
          }
        }

        $this->add('select', "open_pledges[$rowNumber]", '', $options);
      }

      foreach ($this->_fields as $field) {
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

    // CRM-19477: Display Error for Batch Sizes Exceeding php.ini max_input_vars
    // Notes: $this->_elementIndex gives an approximate count of the variables being sent
    // An offset value is set to deal with additional vars that are likely passed.
    // There may be a more accurate way to do this...
    // set an offset to account for other vars we are not counting
    $offset = 50;
    if ((count($this->_elementIndex) + $offset) > ini_get("max_input_vars")) {
      // Avoiding 'ts' for obscure messages.
      CRM_Core_Error::statusBounce(ts('Batch size is too large. Increase value of php.ini setting "max_input_vars" (current val = %1)', [1 => ini_get("max_input_vars")]));
    }

    $this->assign('fields', $this->_fields);
    CRM_Core_Resources::singleton()
      ->addSetting([
        'contact' => [
          'return' => implode(',', $contactReturnProperties),
          'fieldmap' => array_flip($contactReturnProperties),
        ],
      ]);

    // don't set the status message when form is submitted.
    $buttonName = $this->controller->getButtonName('submit');
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
   * @throws \CRM_Core_Exception
   */
  public static function formRule($params, $files, $self) {
    $errors = [];
    $batchTypes = array_flip(CRM_Batch_DAO_Batch::buildOptions('type_id', 'validate'));
    $fields = [
      'total_amount' => ts('Amount'),
      'financial_type' => ts('Financial Type'),
      'payment_instrument' => ts('Payment Method'),
    ];

    //CRM-16480 if contact is selected, validate financial type and amount field.
    foreach ($params['field'] as $key => $value) {
      if (isset($value['trxn_id'])) {
        if (0 < CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_contribution WHERE trxn_id = %1', [1 => [$value['trxn_id'], 'String']])) {
          $errors["field[$key][trxn_id]"] = ts('Transaction ID must be unique within the database');
        }
      }
      foreach ($fields as $field => $label) {
        if (!empty($params['primary_contact_id'][$key]) && empty($value[$field])) {
          $errors["field[$key][$field]"] = ts('%1 is a required field.', [1 => $label]);
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
      $batchTotal += (float) (CRM_Utils_Rule::cleanMoney($value['total_amount'] ?: 0));

      //validate for soft credit fields
      if (!empty($params['soft_credit_contact_id'][$key]) && empty($params['soft_credit_amount'][$key])) {
        $errors["soft_credit_amount[$key]"] = ts('Please enter the soft credit amount.');
      }
      if (!empty($params['soft_credit_amount'][$key]) && CRM_Utils_Rule::cleanMoney($params['soft_credit_amount'][$key]) > CRM_Utils_Rule::cleanMoney($value['total_amount'])) {
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
   *
   * @throws \CRM_Core_Exception
   */
  public function setDefaultValues() {
    $defaults = [];
    if (empty($this->_fields)) {
      return $defaults;
    }
    // for add mode set smart defaults
    if ($this->_action & CRM_Core_Action::ADD) {
      $currentDate = date('Y-m-d H-i-s');

      $completeStatus = CRM_Contribute_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');
      $specialFields = [
        'membership_join_date' => date('Y-m-d'),
        'receive_date' => $currentDate,
        'contribution_status_id' => $completeStatus,
      ];

      for ($rowNumber = 1; $rowNumber <= $this->_batchInfo['item_count']; $rowNumber++) {
        foreach ($specialFields as $key => $value) {
          $defaults['field'][$rowNumber][$key] = $value;
        }
      }
    }
    else {
      // get the cached info from data column of civicrm_batch
      $data = CRM_Core_DAO::getFieldValue('CRM_Batch_BAO_Batch', $this->_batchId, 'data');
      if ($data) {
        $defaults = json_decode($data, TRUE);
        $defaults = $defaults['values'];
      }
    }

    return $defaults;
  }

  /**
   * Process the form after the input has been submitted and validated.
   *
   * @throws \CRM_Core_Exception
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    $params['actualBatchTotal'] = 0;

    // get the profile information
    $batchTypes = array_flip(CRM_Batch_DAO_Batch::buildOptions('type_id', 'validate'));
    if (in_array($this->_batchInfo['type_id'], [$batchTypes['Pledge Payment'], $batchTypes['Contribution']])) {
      $this->processContribution($params);
    }
    elseif ($this->_batchInfo['type_id'] == $batchTypes['Membership']) {
      $params['actualBatchTotal'] = $this->processMembership($params);
    }

    // update batch to close status
    $paramValues = [
      'id' => $this->_batchId,
      // close status
      'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Batch_BAO_Batch', 'status_id', 'Closed'),
      'total' => $params['actualBatchTotal'],
    ];

    CRM_Batch_BAO_Batch::writeRecord($paramValues);

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
   *
   * @throws \CRM_Core_Exception
   */
  private function processContribution(array &$params): bool {

    foreach ($this->submittableMoneyFields as $moneyField) {
      foreach ($params['field'] as $index => $fieldValues) {
        if (isset($fieldValues[$moneyField])) {
          $params['field'][$index][$moneyField] = CRM_Utils_Rule::cleanMoney($params['field'][$index][$moneyField]);
        }
      }
    }
    $params['actualBatchTotal'] = CRM_Utils_Rule::cleanMoney($params['actualBatchTotal']);

    if (isset($params['field'])) {
      foreach ($params['field'] as $key => $value) {
        // if contact is not selected we should skip the row
        if (empty($params['primary_contact_id'][$key])) {
          continue;
        }

        $value['contact_id'] = $params['primary_contact_id'][$key] ?? NULL;

        // update contact information
        $this->updateContactInfo($value);

        //build soft credit params
        if (!empty($params['soft_credit_contact_id'][$key]) && !empty($params['soft_credit_amount'][$key])) {
          $value['soft_credit'][$key]['contact_id'] = $params['soft_credit_contact_id'][$key];
          $value['soft_credit'][$key]['amount'] = CRM_Utils_Rule::cleanMoney($params['soft_credit_amount'][$key]);

          //CRM-15350: if soft-credit-type profile field is disabled or removed then
          //we choose configured SCT default value
          if (array_key_exists('soft_credit_type', $params)) {
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

        if (!empty($value['send_receipt'])) {
          $value['receipt_date'] = date('Y-m-d His');
        }
        // these translations & date handling are required because we are calling BAO directly rather than the api
        $fieldTranslations = [
          'financial_type' => 'financial_type_id',
          'payment_instrument' => 'payment_instrument_id',
          'contribution_source' => 'source',
          'contribution_note' => 'note',
          'contribution_check_number' => 'check_number',
        ];
        foreach ($fieldTranslations as $formField => $baoField) {
          if (isset($value[$formField])) {
            $value[$baoField] = $value[$formField];
          }
          unset($value[$formField]);
        }

        $params['actualBatchTotal'] += $value['total_amount'];
        $value['batch_id'] = $this->_batchId;
        $value['skipRecentView'] = TRUE;

        //finally call contribution create for all the magic
        $contribution = CRM_Contribute_BAO_Contribution::create($value);
        // This code to retrieve the contribution has been moved here from the contribution create
        // api. It may not be required.
        $titleFields = [
          'contact_id',
          'total_amount',
          'currency',
          'financial_type_id',
        ];
        $retrieveRequired = 0;
        foreach ($titleFields as $titleField) {
          if (!isset($contribution->$titleField)) {
            $retrieveRequired = 1;
            break;
          }
        }
        if ($retrieveRequired == 1) {
          $contribution->find(TRUE);
        }
        $batchTypes = array_flip(CRM_Batch_DAO_Batch::buildOptions('type_id', 'validate'));
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
            foreach ($result as $values) {
              if ($values['status'] !== 'Completed') {
                $pledgePaymentId = $values['id'];
                break;
              }
            }
            CRM_Core_DAO::setFieldValue('CRM_Pledge_DAO_PledgePayment', $pledgePaymentId, 'contribution_id', $contribution->id);
            CRM_Pledge_BAO_PledgePayment::updatePledgePaymentStatus($pledgeId,
              [$pledgePaymentId],
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
            [$products, $options] = CRM_Contribute_BAO_Premium::getPremiumProductInfo();

            $value['hidden_Premium'] = 1;
            $value['product_option'] = $options[$value['product_name'][0]][$value['product_name'][1]] ?? NULL;

            $premiumParams = [
              'product_id' => $value['product_name'][0],
              'contribution_id' => $contribution->id,
              'product_option' => $value['product_option'],
              'quantity' => 1,
            ];
            CRM_Contribute_BAO_Contribution::addPremium($premiumParams);
          }
        }
        // end of premium

        //send receipt mail.
        if ($contribution->id && !empty($value['send_receipt'])) {
          $value['from_email_address'] = $this->getFromEmailAddress();
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
   *   Array of submitted values.
   *
   * @return float
   *   batch total monetary amount.
   *
   * @throws \CRM_Core_Exception
   */
  private function processMembership(array $params) {
    $batchTotal = 0;

    if (isset($params['field'])) {
      // @todo - most of the wrangling in this function is because the api is not being used, especially date stuff.
      foreach ($params['field'] as $key => $value) {
        // if contact is not selected we should skip the row
        if (empty($params['primary_contact_id'][$key])) {
          continue;
        }
        $value['contact_id'] = $params['primary_contact_id'][$key];
        $value = $this->standardiseRow($value);
        $this->currentRow = $value;
        $this->currentRowExistingMembership = NULL;
        $this->currentRowIsRenewOption = (int) ($params['member_option'][$key] ?? 1);

        // update contact information
        $this->updateContactInfo($value);

        //check for custom data
        $value['custom'] = CRM_Core_BAO_CustomField::postProcess($this->currentRow,
          $key,
          'Membership',
          $value['membership_type_id']
        );

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
            $value['soft_credit'][$key]['soft_credit_type_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_ContributionSoft', 'soft_credit_type_id', 'Gift');
          }
        }
        $batchTotal += $value['total_amount'];
        $value['batch_id'] = $this->_batchId;
        $value['skipRecentView'] = TRUE;

        $order = new CRM_Financial_BAO_Order();
        // We use the override total amount because we are dealing with a
        // possibly tax_inclusive total, which is assumed for the override total.
        $order->setOverrideTotalAmount((float) $value['total_amount']);
        $order->setLineItem([
          'membership_type_id' => $value['membership_type_id'],
          'financial_type_id' => $value['financial_type_id'],
        ], $key);
        $order->setEntityParameters($this->getCurrentRowMembershipParams(), $key);

        if (!empty($order->getLineItems())) {
          $value['lineItems'] = [$order->getPriceSetID() => $order->getPriceFieldIndexedLineItems()];
          $value['processPriceSet'] = TRUE;
        }
        // end of contribution related section
        if ($this->currentRowIsRenew()) {
          // The following parameter setting may be obsolete.
          $this->_params = $params;

          $formDates = [
            'end_date' => $value['membership_end_date'] ?? NULL,
            'start_date' => $value['membership_start_date'] ?? NULL,
          ];

          $membership = $this->legacyProcessMembership(
            $value['custom'], $formDates
          );

          // make contribution entry
          $contrbutionParams = array_merge($value, ['membership_id' => $membership->id]);
          $contrbutionParams['skipCleanMoney'] = TRUE;
          // @todo - calling this from here is pretty hacky since it is called from membership.create anyway
          // This form should set the correct params & not call this fn directly.
          CRM_Member_BAO_Membership::recordMembershipContribution($contrbutionParams);
          $this->setCurrentRowMembershipID($membership->id);
        }
        else {
          $createdOrder = civicrm_api3('Order', 'create', [
            'line_items' => $order->getLineItemForV3OrderApi(),
            'receive_date' => $this->currentRow['receive_date'],
            'check_number' => $this->currentRow['check_number'] ?? '',
            'contact_id' => $this->getCurrentRowContactID(),
            'batch_id' => $this->_batchId,
            'financial_type_id' => $this->currentRow['financial_type_id'],
            'payment_instrument_id' => $this->currentRow['payment_instrument_id'],
          ]);
          $this->currentRowContributionID = $createdOrder['id'];

          $this->setCurrentRowMembershipID($createdOrder['values'][$this->getCurrentRowContributionID()]['line_item'][0]['entity_id']);
          if ($this->getCurrentRowPaymentStatus() === 'Completed') {
            civicrm_api3('Payment', 'create', [
              'total_amount' => $order->getTotalAmount() + $order->getTotalTaxAmount(),
              'check_number' => $this->currentRow['check_number'] ?? '',
              'trxn_date' => $this->currentRow['receive_date'],
              'trxn_id' => $this->currentRow['trxn_id'] ?? '',
              'payment_instrument_id' => $this->currentRow['payment_instrument_id'],
              'contribution_id' => $this->getCurrentRowContributionID(),
              'is_send_contribution_notification' => FALSE,
            ]);
          }

          if (in_array($this->getCurrentRowPaymentStatus(), ['Failed', 'Cancelled'])) {
            Contribution::update()
              ->addValue('contribution_status_id', $this->currentRow['contribution_status_id'])
              ->addWhere('id', '=', $this->getCurrentRowContributionID())
              ->execute();
          }
        }
        //process premiums
        if (!empty($value['product_name'])) {
          if ($value['product_name'][0] > 0) {
            [, $options] = CRM_Contribute_BAO_Premium::getPremiumProductInfo();

            $value['hidden_Premium'] = 1;
            $value['product_option'] = $options[$value['product_name'][0]][$value['product_name'][1]] ?? NULL;

            $premiumParams = [
              'product_id' => $value['product_name'][0],
              'contribution_id' => $this->getCurrentRowContributionID(),
              'product_option' => $value['product_option'],
              'quantity' => 1,
            ];
            CRM_Contribute_BAO_Contribution::addPremium($premiumParams);
          }
        }
        // end of premium

        //send receipt mail.
        if ($this->getCurrentRowMembershipID() && !empty($value['send_receipt'])) {
          $value['membership_id'] = $this->getCurrentRowMembershipID();
          $this->emailReceipt($this, $value);
        }
      }
    }
    return $batchTotal;
  }

  /**
   * Send email receipt.
   *
   * @param CRM_Batch_Form_Entry $form
   *   Form object.
   * @param array $formValues
   *
   * @return bool
   *   true if mail was sent successfully
   * @throws \CRM_Core_Exception
   *
   */
  protected function emailReceipt($form, &$formValues): bool {
    // @todo figure out how much of the stuff below is genuinely shared with the batch form & a logical shared place.
    if (!empty($formValues['payment_instrument_id'])) {
      $paymentInstrument = CRM_Contribute_PseudoConstant::paymentInstrument();
      $formValues['paidBy'] = $paymentInstrument[$formValues['payment_instrument_id']];
    }

    // @todo - as of 5.74 module is noisy deprecated - can stop assigning around 5.80.
    $form->assign('module', 'Membership');

    $form->assign('receiptType', $this->currentRowIsRenew() ? 'membership renewal' : 'membership signup');
    // @todo - as of 5.74 form values is noisy deprecated - can stop assigning around 5.80.
    $form->assign('formValues', $formValues);

    [$contributorDisplayName, $contributorEmail]
      = CRM_Contact_BAO_Contact_Location::getEmailDetails($formValues['contact_id']);
    $receiptContactId = $formValues['contact_id'];

    CRM_Core_BAO_MessageTemplate::sendTemplate(
      [
        'workflow' => 'membership_offline_receipt',
        'from' => $this->getFromEmailAddress(),
        'toName' => $contributorDisplayName,
        'toEmail' => $contributorEmail,
        'PDFFilename' => ts('receipt') . '.pdf',
        'isEmailPdf' => Civi::settings()->get('invoice_is_email_pdf'),
        'isTest' => (bool) ($form->_action & CRM_Core_Action::PREVIEW),
        'modelProps' => [
          'contributionID' => $this->getCurrentRowContributionID(),
          'contactID' => $receiptContactId,
          'membershipID' => $this->getCurrentRowMembershipID(),
        ],
      ]
    );

    Contribution::update(FALSE)
      ->addWhere('id', '=', $this->getCurrentRowContributionID())
      ->setValues(['receipt_date', 'now'])
      ->execute();

    return TRUE;
  }

  /**
   * Update contact information.
   *
   * @param array $value
   *   Associated array of submitted values.
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  private function updateContactInfo(array &$value) {
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
   * @return float
   * @throws \CRM_Core_Exception
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
   * @deprecated since 5.82 will be removed around 5.86
   *
   * @param array $params
   *
   * @return bool
   *
   * @throws \CRM_Core_Exception
   */
  public function testProcessContribution($params) {
    return $this->processContribution($params);
  }

  /**
   * @param $customFieldsFormatted
   * @param array $formDates
   *
   * @return CRM_Member_BAO_Membership
   *
   * @throws \CRM_Core_Exception
   */
  protected function legacyProcessMembership($customFieldsFormatted, $formDates = []): CRM_Member_DAO_Membership {
    $updateStatusId = FALSE;
    $changeToday = NULL;
    $numRenewTerms = 1;
    $format = '%Y%m%d';
    $ids = [];
    $isPayLater = NULL;
    $memParams = $this->getCurrentRowMembershipParams();
    $currentMembership = $this->getCurrentMembership();

    // Now Renew the membership
    if (!$currentMembership['is_current_member']) {
      // membership is not CURRENT

      // CRM-7297 Membership Upsell - calculate dates based on new membership type
      $dates = CRM_Member_BAO_MembershipType::getRenewalDatesForMembershipType($currentMembership['id'],
        $changeToday,
        $this->getCurrentRowMembershipTypeID(),
        $numRenewTerms
      );

      foreach (['start_date', 'end_date'] as $dateType) {
        $memParams[$dateType] = $memParams[$dateType] ?: ($dates[$dateType] ?? NULL);
      }

      $ids['membership'] = $currentMembership['id'];

      //set the log start date.
      $memParams['log_start_date'] = CRM_Utils_Date::customFormat($dates['log_start_date'], $format);
    }
    else {

      // CURRENT Membership
      $membership = new CRM_Member_DAO_Membership();
      $membership->id = $currentMembership['id'];
      $membership->find(TRUE);
      // CRM-7297 Membership Upsell - calculate dates based on new membership type
      $dates = CRM_Member_BAO_MembershipType::getRenewalDatesForMembershipType($membership->id,
        $changeToday,
        $this->getCurrentRowMembershipTypeID(),
        $numRenewTerms
      );

      // Insert renewed dates for CURRENT membership
      $memParams['join_date'] = CRM_Utils_Date::isoToMysql($membership->join_date);
      $memParams['start_date'] = $formDates['start_date'] ?? CRM_Utils_Date::isoToMysql($membership->start_date);
      $memParams['end_date'] = $formDates['end_date'] ?? NULL;
      if (empty($memParams['end_date'])) {
        $memParams['end_date'] = $dates['end_date'] ?? NULL;
      }

      //set the log start date.
      $memParams['log_start_date'] = CRM_Utils_Date::customFormat($dates['log_start_date'], $format);

      if (!empty($currentMembership['id'])) {
        $ids['membership'] = $currentMembership['id'];
      }
      $memParams['membership_activity_status'] = $isPayLater ? 'Scheduled' : 'Completed';
    }

    //CRM-4555
    //if we decided status here and want to skip status
    //calculation in create( ); then need to pass 'skipStatusCal'.
    if ($updateStatusId) {
      $memParams['status_id'] = $updateStatusId;
      $memParams['skipStatusCal'] = TRUE;
    }

    //since we are renewing,
    //make status override false.
    $memParams['is_override'] = FALSE;
    $memParams['custom'] = $customFieldsFormatted;
    // Load all line items & process all in membership. Don't do in contribution.
    // Relevant tests in api_v3_ContributionPageTest.
    // @todo stop passing $ids (membership and userId may be set by this point)
    // $ids['membership'] is the "current membership ID"
    $membership = CRM_Member_BAO_Membership::create($memParams, $ids);

    // not sure why this statement is here, seems quite odd :( - Lobo: 12/26/2010
    // related to: http://forum.civicrm.org/index.php/topic,11416.msg49072.html#msg49072
    $membership->find(TRUE);

    return $membership;
  }

  /**
   * @return string
   * @throws \CRM_Core_Exception
   */
  private function getFromEmailAddress(): string {
    $domainEmail = CRM_Core_BAO_Domain::getNameAndEmail();
    return "$domainEmail[0] <$domainEmail[1]>";
  }

  /**
   * Standardise the values in the row from profile-weirdness to civi-standard.
   *
   * The row uses odd field names such as financial_type rather than financial
   * type id. We standardise at this point.
   *
   * @param array $row
   *
   * @return array
   */
  private function standardiseRow(array $row): array {
    foreach ($row as $fieldKey => $fieldValue) {
      if (isset($this->_fields[$fieldKey]) && $this->_fields[$fieldKey]['data_type'] === 'Money') {
        $row[$fieldKey] = CRM_Utils_Rule::cleanMoney($fieldValue);
      }
    }
    $renameFieldMapping = [
      'financial_type' => 'financial_type_id',
      'payment_instrument' => 'payment_instrument_id',
      'membership_source' => 'source',
      'membership_status' => 'status_id',
    ];
    foreach ($renameFieldMapping as $weirdProfileName => $betterName) {
      // Check if isset as some like payment instrument and source are optional.
      if (isset($row[$weirdProfileName]) && empty($row[$betterName])) {
        $row[$betterName] = $row[$weirdProfileName];
        unset($row[$weirdProfileName]);
      }
    }

    // The latter format would be normal here - it's unclear if it is sometimes in the former format.
    $row['membership_type_id'] ??= $row['membership_type'][1];
    unset($row['membership_type']);
    // total_amount is required.
    $row['total_amount'] = (float) $row['total_amount'];
    return $row;
  }

  /**
   * Is the current row a renewal.
   *
   * @return bool
   *
   * @throws \CRM_Core_Exception
   */
  private function currentRowIsRenew(): bool {
    return $this->currentRowIsRenewOption === 2 && $this->getCurrentMembership();
  }

  /**
   * Get any current membership for the current row contact, for the same member organization.
   *
   * @return array|bool
   *
   * @throws \CRM_Core_Exception
   */
  protected function getCurrentMembership() {
    if (!isset($this->currentRowExistingMembership)) {
      // CRM-7297 - allow membership type to be changed during renewal so long as the parent org of new membershipType
      // is the same as the parent org of an existing membership of the contact
      $this->currentRowExistingMembership = CRM_Member_BAO_Membership::getContactMembership($this->getCurrentRowContactID(), $this->getCurrentRowMembershipTypeID(),
        FALSE, NULL, TRUE
      );
      if ($this->currentRowExistingMembership) {
        // Check and fix the membership if it is STALE
        CRM_Member_BAO_Membership::fixMembershipStatusBeforeRenew($this->currentRowExistingMembership);
      }
    }
    return $this->currentRowExistingMembership;
  }

  /**
   * Get the params as related to the membership entity.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  private function getCurrentRowMembershipParams(): array {
    return array_merge($this->getCurrentRowCustomParams(), [
      'start_date' => $this->currentRow['membership_start_date'] ?? NULL,
      'end_date' => $this->currentRow['membership_end_date'] ?? NULL,
      'join_date' => $this->currentRow['membership_join_date'] ?? NULL,
      'campaign_id' => $this->currentRow['member_campaign_id'] ?? NULL,
      'source' => $this->currentRow['source'] ?? (!$this->currentRowIsRenew() ? ts('Batch entry') : ''),
      'membership_type_id' => $this->currentRow['membership_type_id'],
      'contact_id' => $this->getCurrentRowContactID(),
    ]);
  }

  /**
   * Get the custom value parameters from the current row.
   *
   * @return array
   */
  private function getCurrentRowCustomParams(): array {
    $return = [];
    foreach ($this->currentRow as $field => $value) {
      if (str_starts_with($field, 'custom_')) {
        $return[$field] = $value;
      }
    }
    return $return;
  }

}
