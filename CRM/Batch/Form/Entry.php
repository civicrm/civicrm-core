<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * This class provides the functionality for batch entry for contributions/memberships
 */
class CRM_Batch_Form_Entry extends CRM_Core_Form {

  /**
   * maximum profile fields that will be displayed
   *
   */
  protected $_rowCount = 1;

  /**
   * Batch id
   */
  protected $_batchId;

  /**
   * Batch information
   */
  protected $_batchInfo = array();

  /**
   * store the profile id associated with the batch type
   */
  protected $_profileId;

  public $_action;

  public $_mode;

  public $_params;

  public $_membershipId = null;
  /**
   * when not to reset sort_name
   */
  protected $_preserveDefault = TRUE;

  /**
   * Contact fields
   */
  protected $_contactFields = array();

  /**
   * Fields array of fields in the batch profile
   * (based on the uf_field table data)
   * (this can't be protected as it is passed into the CRM_Contact_Form_Task_Batch::parseStreetAddress function
   * (although a future refactoring might hopefully change that so it uses the api & the function is not
   * required
   * @var array
   */
  public $_fields = array();
  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
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
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  function buildQuickForm() {
    if (!$this->_profileId) {
      CRM_Core_Error::fatal(ts('Profile for bulk data entry is missing.'));
    }

    $this->addElement('hidden', 'batch_id', $this->_batchId);

    // get the profile information
    if ($this->_batchInfo['type_id'] == 1) {
      CRM_Utils_System::setTitle(ts('Batch Data Entry for Contributions'));
      $customFields = CRM_Core_BAO_CustomField::getFields('Contribution');
    }
    else {
      CRM_Utils_System::setTitle(ts('Batch Data Entry for Memberships'));
      $customFields = CRM_Core_BAO_CustomField::getFields('Membership');
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
          'isDefault' => TRUE
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Save & Continue Later'),
        )
      )
    );

    $this->assign('rowCount', $this->_batchInfo['item_count'] + 1);

    $fileFieldExists = FALSE;
    $preserveDefaultsArray = array(
      'first_name', 'last_name', 'middle_name',
      'organization_name',
      'household_name',
    );

    $contactTypes = array('Contact', 'Individual', 'Household', 'Organization');
    $contactReturnProperties = array();
    for ($rowNumber = 1; $rowNumber <= $this->_batchInfo['item_count']; $rowNumber++) {
      $this->addEntityRef("primary_contact_id[{$rowNumber}]", '', array('create' => TRUE, 'placeholder' => ts('- select -')));

      // special field specific to membership batch udpate
      if ($this->_batchInfo['type_id'] == 2) {
        $options = array(
          1 => ts('Add Membership'),
          2 => ts('Renew Membership'),
        );
        $this->add('select', "member_option[$rowNumber]", '', $options);
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
    ->addSetting(array('contact' => array(
      'return' => implode(',', $contactReturnProperties),
      'fieldmap' => array_flip($contactReturnProperties),
    )));

    // don't set the status message when form is submitted.
    $buttonName = $this->controller->getButtonName('submit');

    if ($suppressFields && $buttonName != '_qf_Entry_next') {
      CRM_Core_Session::setStatus(ts("FILE or Autocomplete Select type field(s) in the selected profile are not supported for Batch Update."), ts("Some fields have been excluded."), "info");
    }
  }

  /**
   * form validations
   *
   * @param array $params   posted values of the form
   * @param array $files    list of errors to be posted back to the form
   * @param array $self     form object
   *
   * @return array list of errors to be posted back to the form
   * @static
   * @access public
   */
  static function formRule($params, $files, $self) {
    $errors = array();

    if (!empty($params['_qf_Entry_upload_force'])) {
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
      if ( $self->_batchInfo['type_id'] == 2 ) {
        if (empty($value['membership_type'][1])) {
          $errors["field[$key][membership_type]"] = ts('Membership type is a required field.');
        }
      }
    }

    if ($batchTotal != $self->_batchInfo['total']) {
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
   * Override default cancel action
   */
  function cancelAction() {
    // redirect to batch listing
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/batch', 'reset=1'));
    CRM_Utils_System::civiExit();
  }

  /**
   * This function sets the default values for the form.
   *
   * @access public
   *
   * @return void
   */
  function setDefaultValues() {
    if (empty($this->_fields)) {
      return;
    }

    // for add mode set smart defaults
    if ( $this->_action & CRM_Core_Action::ADD ) {
      list( $currentDate, $currentTime ) = CRM_Utils_Date::setDateDefaults( NULL, 'activityDateTime' );

      //get all status
      $allStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
      $completeStatus = array_search( 'Completed', $allStatus );
      $specialFields = array(
        'join_date' => $currentDate,
        'receive_date' => $currentDate,
        'receive_date_time' => $currentTime,
        'contribution_status_id' => $completeStatus
      );

      for ($rowNumber = 1; $rowNumber <= $this->_batchInfo['item_count']; $rowNumber++) {
        foreach ($specialFields as $key => $value ) {
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
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return void
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);

    $params['actualBatchTotal'] = 0;

    // get the profile information
    if ($this->_batchInfo['type_id'] == 1) {
      $this->processContribution($params);
    }
    else {
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
   * process contribution records
   *
   * @param array $params associated array of submitted values
   *
   * @access public
   *
   * @return void
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

        $value['custom'] = CRM_Core_BAO_CustomField::postProcess($value,
          CRM_Core_DAO::$_nullObject,
          NULL,
          'Contribution'
        );

        foreach ($dates as $val) {
          if (!empty($value[$val])) {
            $value[$val] = CRM_Utils_Date::processDate( $value[$val], $value[$val . '_time'], TRUE );
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
          if(isset($value[$formField])) {
            $value[$baoField] = $value[$formField];
          }
          unset($value[$formField]);
        }

        $params['actualBatchTotal'] += $value['total_amount'];
        $value['batch_id'] = $this->_batchId;
        $value['skipRecentView'] = TRUE;

        // build line item params
        $this->_priceSet['fields'][$priceFieldID]['options'][$priceFieldValueID ]['amount'] =  $value['total_amount'];
        $value['price_'. $priceFieldID] = 1;

        $lineItem = array();
        CRM_Price_BAO_PriceSet::processAmount($this->_priceSet['fields'], $value, $lineItem[$priceSetId]);

        //unset amount level since we always use quick config price set
        unset($value['amount_level']);

        //CRM-11529 for back office transactions
        //when financial_type_id is passed in form, update the
        //line items with the financial type selected in form
        if (!empty($value['financial_type_id']) && !empty($lineItem[$priceSetId])) {
          foreach ($lineItem[$priceSetId] as &$values) {
            $values['financial_type_id'] = $value['financial_type_id'];
          }
        }
        $value['line_item'] = $lineItem;

        //finally call contribution create for all the magic
        $contribution = CRM_Contribute_BAO_Contribution::create($value, CRM_Core_DAO::$_nullArray);

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
        if ( $contribution->id && !empty($value['send_receipt'])) {
            // add the domain email id
            $domainEmail = CRM_Core_BAO_Domain::getNameAndEmail();
            $domainEmail = "$domainEmail[0] <$domainEmail[1]>";

            $value['from_email_address'] = $domainEmail;
            $value['contribution_id'] = $contribution->id;
            CRM_Contribute_Form_AdditionalInfo::emailReceipt( $this, $value );
        }
      }
    }
    return TRUE;
  }
  //end of function

  /**
   * process membership records
   *
   * @param array $params associated array of submitted values
   *
   * @access public
   *
   * @return bool
   */
  private function processMembership(&$params) {
    $dateTypes = array(
      'join_date' => 'joinDate',
      'membership_start_date' => 'startDate',
      'membership_end_date' => 'endDate'
    );

    $dates = array(
      'join_date',
      'start_date',
      'end_date',
      'reminder_date'
    );

    // get the price set associated with offline memebership
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
          $customFields,
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
          $value['receive_date'] = CRM_Utils_Date::processDate( $value['receive_date'], $value['receive_date_time'] , TRUE );
        }

        $params['actualBatchTotal'] += $value['total_amount'];

        unset($value['financial_type']);
        unset($value['payment_instrument']);

        $value['batch_id'] = $this->_batchId;
        $value['skipRecentView'] = TRUE;

        // make entry in line item for contribution

        $editedFieldParams = array(
          'price_set_id' => $priceSetId,
          'name' => $value['membership_type'][0]
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
            'membership_type_id' => $value['membership_type_id']
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

        $value['is_renew'] = false;
        if (!empty($params['member_option']) && CRM_Utils_Array::value( $key, $params['member_option'] ) == 2 ) {
          $this->_params = $params;
          $value['is_renew'] = true;
          $membership = CRM_Member_BAO_Membership::renewMembershipFormWrapper(
            $value['contact_id'],
            $value['membership_type_id'],
            FALSE, $this, NULL, NULL,
            $value['custom']
          );

          // make contribution entry
          CRM_Member_BAO_Membership::recordMembershipContribution( array_merge($value, array('membership_id' => $membership->id)));
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
              'contribution_id' => $value['contribution_id'],
              'product_option' => $value['product_option'],
              'quantity' => 1,
            );
            CRM_Contribute_BAO_Contribution::addPremium($premiumParams);
          }
        }
        // end of premium

        //send receipt mail.
        if ( $membership->id && !empty($value['send_receipt'])) {

            // add the domain email id
            $domainEmail = CRM_Core_BAO_Domain::getNameAndEmail();
            $domainEmail = "$domainEmail[0] <$domainEmail[1]>";

            $value['from_email_address'] = $domainEmail;
            $value['membership_id']      = $membership->id;
            CRM_Member_Form_Membership::emailReceipt( $this, $value, $membership );
        }
      }
    }
    return TRUE;
  }

  /**
   * update contact information
   *
   * @param array $value associated array of submitted values
   *
   * @access public
   *
   * @return void
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
   * Function exists purely for unit testing purposes. If you feel tempted to use this in live code
   * then it probably means there is some functionality that needs to be moved
   * out of the form layer
   *
   * @param unknown_type $params
   *
   * @return bool
   */
  function testProcessMembership($params) {
    return $this->processMembership($params);
  }

  /**
   * Function exists purely for unit testing purposes. If you feel tempted to use this in live code
   * then it probably means there is some functionality that needs to be moved
   * out of the form layer
   *
   * @param array $params
   *
   * @return bool
   */
  function testProcessContribution($params) {
    return $this->processContribution($params);
  }
}

