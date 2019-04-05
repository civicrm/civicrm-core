<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * form to process actions on the group aspect of Custom Data
 */
class CRM_Contribute_Form_ContributionPage_Amount extends CRM_Contribute_Form_ContributionPage {

  /**
   * Contribution amount block.
   *
   * @var array
   */
  protected $_amountBlock = [];

  /**
   * Constants for number of options for data types of multiple option.
   */
  const NUM_OPTION = 11;

  /**
   * Build the form object.
   */
  public function buildQuickForm() {

    // do u want to allow a free form text field for amount
    $this->addElement('checkbox', 'is_allow_other_amount', ts('Allow other amounts'), NULL, ['onclick' => "minMax(this);showHideAmountBlock( this, 'is_allow_other_amount' );"]);
    $this->add('text', 'min_amount', ts('Minimum Amount'), ['size' => 8, 'maxlength' => 8]);
    $this->addRule('min_amount', ts('Please enter a valid money value (e.g. %1).', [1 => CRM_Utils_Money::format('9.99', ' ')]), 'money');

    $this->add('text', 'max_amount', ts('Maximum Amount'), ['size' => 8, 'maxlength' => 8]);
    $this->addRule('max_amount', ts('Please enter a valid money value (e.g. %1).', [1 => CRM_Utils_Money::format('99.99', ' ')]), 'money');

    //CRM-12055
    $this->add('text', 'amount_label', ts('Contribution Amounts Label'));

    $default = [$this->createElement('radio', NULL, NULL, NULL, 0)];
    $this->add('hidden', "price_field_id", '', ['id' => "price_field_id"]);
    $this->add('hidden', "price_field_other", '', ['id' => "price_field_option"]);
    for ($i = 1; $i <= self::NUM_OPTION; $i++) {
      // label
      $this->add('text', "label[$i]", ts('Label'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'label'));

      $this->add('hidden', "price_field_value[$i]", '', ['id' => "price_field_value[$i]"]);

      // value
      $this->add('text', "value[$i]", ts('Value'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'value'));
      $this->addRule("value[$i]", ts('Please enter a valid money value (e.g. %1).', [1 => CRM_Utils_Money::format('99.99', ' ')]), 'money');

      // default
      $default[] = $this->createElement('radio', NULL, NULL, NULL, $i);
    }

    $this->addGroup($default, 'default');

    $this->addElement('checkbox', 'amount_block_is_active', ts('Contribution Amounts section enabled'), NULL, ['onclick' => "showHideAmountBlock( this, 'amount_block_is_active' );"]);

    $this->addElement('checkbox', 'is_monetary', ts('Execute real-time monetary transactions'));

    $paymentProcessors = CRM_Financial_BAO_PaymentProcessor::getAllPaymentProcessors('live');
    $recurringPaymentProcessor = $futurePaymentProcessor = $paymentProcessor = [];

    if (!empty($paymentProcessors)) {
      foreach ($paymentProcessors as $id => $processor) {
        if ($id != 0) {
          $paymentProcessor[$id] = $processor['name'];
        }
        if (CRM_Utils_Array::value('is_recur', $processor)) {
          $recurringPaymentProcessor[] = $id;
        }
        if (CRM_Utils_Array::value('object', $processor) && $processor['object']->supports('FutureRecurStartDate')) {
          $futurePaymentProcessor[] = $id;
        }
      }
    }
    if (count($recurringPaymentProcessor)) {
      $this->assign('recurringPaymentProcessor', $recurringPaymentProcessor);
    }
    if (count($futurePaymentProcessor)) {
      $this->assign('futurePaymentProcessor', $futurePaymentProcessor);
    }
    if (count($paymentProcessor)) {
      $this->assign('paymentProcessor', $paymentProcessor);
    }

    $this->addCheckBox('payment_processor', ts('Payment Processor'),
      array_flip($paymentProcessor),
      NULL, NULL, NULL, NULL,
      ['&nbsp;&nbsp;', '&nbsp;&nbsp;', '&nbsp;&nbsp;', '<br/>']
    );

    //check if selected payment processor supports recurring payment
    if (!empty($recurringPaymentProcessor)) {
      $this->addElement('checkbox', 'is_recur', ts('Recurring Contributions'), NULL,
        ['onclick' => "showHideByValue('is_recur',true,'recurFields','table-row','radio',false);"]
      );
      $this->addCheckBox('recur_frequency_unit', ts('Supported recurring units'),
        CRM_Core_OptionGroup::values('recur_frequency_units', FALSE, FALSE, TRUE),
        NULL, NULL, NULL, NULL,
        ['&nbsp;&nbsp;', '&nbsp;&nbsp;', '&nbsp;&nbsp;', '<br/>'], TRUE
      );
      $this->addElement('checkbox', 'is_recur_interval', ts('Support recurring intervals'));
      $this->addElement('checkbox', 'is_recur_installments', ts('Offer installments'));
    }

    // add pay later options
    $this->addElement('checkbox', 'is_pay_later', ts('Pay later option'), NULL);
    $this->addElement('textarea', 'pay_later_text', ts('Pay later label'),
      CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionPage', 'pay_later_text'),
      FALSE
    );
    $this->add('wysiwyg', 'pay_later_receipt', ts('Pay Later Instructions'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionPage', 'pay_later_receipt'));
    $this->addElement('checkbox', 'is_billing_required', ts('Billing address required'));

    //add partial payment options

    // add price set fields
    $price = CRM_Price_BAO_PriceSet::getAssoc(FALSE, 'CiviContribute');
    if (CRM_Utils_System::isNull($price)) {
      $this->assign('price', FALSE);
    }
    else {
      $this->assign('price', TRUE);
    }
    $this->add('select', 'price_set_id', ts('Price Set'),
      [
        '' => ts('- none -'),
      ] + $price,
      NULL, ['onchange' => "showHideAmountBlock( this.value, 'price_set_id' );"]
    );
    //CiviPledge fields.
    $config = CRM_Core_Config::singleton();
    if (in_array('CiviPledge', $config->enableComponents)) {
      $this->assign('civiPledge', TRUE);
      $this->addElement('checkbox', 'is_pledge_active', ts('Pledges'),
        NULL, ['onclick' => "showHideAmountBlock( this, 'is_pledge_active' ); return showHideByValue('is_pledge_active',true,'pledgeFields','table-row','radio',false);"]
      );
      $this->addCheckBox('pledge_frequency_unit', ts('Supported pledge frequencies'),
        CRM_Core_OptionGroup::values('recur_frequency_units', FALSE, FALSE, TRUE),
        NULL, NULL, NULL, NULL,
        ['&nbsp;&nbsp;', '&nbsp;&nbsp;', '&nbsp;&nbsp;', '<br/>'], TRUE
      );
      $this->addElement('checkbox', 'is_pledge_interval', ts('Allow frequency intervals'));
      $this->addElement('text', 'initial_reminder_day', ts('Send payment reminder'), ['size' => 3]);
      $this->addElement('text', 'max_reminders', ts('Send up to'), ['size' => 3]);
      $this->addElement('text', 'additional_reminder_day', ts('Send additional reminders'), ['size' => 3]);
      if (!empty($futurePaymentProcessor)) {
        // CRM-18854
        $this->addElement('checkbox', 'adjust_recur_start_date', ts('Adjust Recurring Start Date'), NULL,
          ['onclick' => "showHideByValue('adjust_recur_start_date',true,'recurDefaults','table-row','radio',false);"]
        );
        $this->addDate('pledge_calendar_date', ts('Specific Calendar Date'));
        $month = CRM_Utils_Date::getCalendarDayOfMonth();
        $this->add('select', 'pledge_calendar_month', ts('Specific day of Month'), $month);
        $pledgeDefaults = [
          'contribution_date' => ts('Day of Contribution'),
          'calendar_date' => ts('Specific Calendar Date'),
          'calendar_month' => ts('Specific day of Month'),
        ];
        $this->addRadio('pledge_default_toggle', ts('Recurring Contribution Start Date Default'), $pledgeDefaults, ['allowClear' => FALSE], '<br/><br/>');
        $this->addElement('checkbox', 'is_pledge_start_date_visible', ts('Show Recurring Donation Start Date?'), NULL);
        $this->addElement('checkbox', 'is_pledge_start_date_editable', ts('Allow Edits to Recurring Donation Start date?'), NULL);
      }
    }

    //add currency element.
    $this->addCurrency('currency', ts('Currency'));

    $this->addFormRule(['CRM_Contribute_Form_ContributionPage_Amount', 'formRule'], $this);

    parent::buildQuickForm();
  }

  /**
   * Set default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();

    if (empty($defaults['pay_later_text'])) {
      $defaults['pay_later_text'] = ts('I will send payment by check');
    }

    if (!empty($defaults['amount_block_is_active'])) {

      if ($priceSetId = CRM_Price_BAO_PriceSet::getFor('civicrm_contribution_page', $this->_id, NULL)) {
        if ($isQuick = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $priceSetId, 'is_quick_config')) {
          $this->assign('isQuick', $isQuick);
          //$priceField = CRM_Core_DAO::getFieldValue( 'CRM_Price_DAO_PriceField', $priceSetId, 'id', 'price_set_id' );
          $options = $pFIDs = [];
          $priceFieldParams = ['price_set_id' => $priceSetId];
          $priceFields = CRM_Core_DAO::commonRetrieveAll('CRM_Price_DAO_PriceField', 'price_set_id', $priceSetId, $pFIDs, $return = [
              'html_type',
              'name',
              'is_active',
              'label',
            ]);
          foreach ($priceFields as $priceField) {
            if ($priceField['id'] && $priceField['html_type'] == 'Radio' && $priceField['name'] == 'contribution_amount') {
              $defaults['price_field_id'] = $priceField['id'];
              $priceFieldOptions = CRM_Price_BAO_PriceFieldValue::getValues($priceField['id'], $options, 'id', 1);
              if (empty($priceFieldOptions)) {
                continue;
              }
              $countRow = 0;
              $defaults['amount_label'] = $priceField['label'];
              foreach ($options as $optionId => $optionValue) {
                $countRow++;
                $defaults['value'][$countRow] = $optionValue['amount'];
                $defaults['label'][$countRow] = CRM_Utils_Array::value('label', $optionValue);
                $defaults['name'][$countRow] = CRM_Utils_Array::value('name', $optionValue);
                $defaults['weight'][$countRow] = $optionValue['weight'];

                $defaults["price_field_value"][$countRow] = $optionValue['id'];
                if ($optionValue['is_default']) {
                  $defaults['default'] = $countRow;
                }
              }
            }
            elseif ($priceField['id'] && $priceField['html_type'] == 'Text' && $priceField['name'] = 'other_amount' && $priceField['is_active']) {
              $defaults['price_field_other'] = $priceField['id'];
              if (!isset($defaults['amount_label'])) {
                $defaults['amount_label'] = $priceField['label'];
              }
            }
          }
        }
      }

      if (empty($defaults['amount_label'])) {
        $defaults['amount_label'] = ts('Contribution Amount');
      }

      if (!empty($defaults['value']) && is_array($defaults['value'])) {

        // CRM-4038: fix value display
        foreach ($defaults['value'] as & $amount) {
          $amount = trim(CRM_Utils_Money::format($amount, ' '));
        }
      }
    }

    // fix the display of the monetary value, CRM-4038
    if (isset($defaults['min_amount'])) {
      $defaults['min_amount'] = CRM_Utils_Money::format($defaults['min_amount'], NULL, '%a');
    }
    if (isset($defaults['max_amount'])) {
      $defaults['max_amount'] = CRM_Utils_Money::format($defaults['max_amount'], NULL, '%a');
    }

    if (!empty($defaults['payment_processor'])) {
      $defaults['payment_processor'] = array_fill_keys(explode(CRM_Core_DAO::VALUE_SEPARATOR,
        $defaults['payment_processor']
      ), '1');
    }
    return $defaults;
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param $self
   *
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields, $files, $self) {
    $errors = [];
    //as for separate membership payment we has to have
    //contribution amount section enabled, hence to disable it need to
    //check if separate membership payment enabled,
    //if so disable first separate membership payment option
    //then disable contribution amount section. CRM-3801,

    $membershipBlock = new CRM_Member_DAO_MembershipBlock();
    $membershipBlock->entity_table = 'civicrm_contribution_page';
    $membershipBlock->entity_id = $self->_id;
    $membershipBlock->is_active = 1;
    $hasMembershipBlk = FALSE;
    if ($membershipBlock->find(TRUE)) {
      if (!empty($fields['amount_block_is_active']) &&
        ($setID = CRM_Price_BAO_PriceSet::getFor('civicrm_contribution_page', $self->_id, NULL, 1))
      ) {
        $extends = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $setID, 'extends');
        if ($extends && $extends == CRM_Core_Component::getComponentID('CiviMember')) {
          $errors['amount_block_is_active'] = ts('You cannot use a Membership Price Set when the Contribution Amounts section is enabled. Click the Memberships tab above, and select your Membership Price Set on that form. Membership Price Sets may include additional fields for non-membership options that require an additional fee (e.g. magazine subscription) or an additional voluntary contribution.');
          return $errors;
        }
      }
      $hasMembershipBlk = TRUE;
      if ($membershipBlock->is_separate_payment && empty($fields['amount_block_is_active'])) {
        $errors['amount_block_is_active'] = ts('To disable Contribution Amounts section you need to first disable Separate Membership Payment option from Membership Settings.');
      }

      //CRM-16165, Don't allow reccuring contribution if membership block contain any renewable membership option
      $membershipTypes = unserialize($membershipBlock->membership_types);
      if (!empty($fields['is_recur']) && !empty($membershipTypes)) {
        if (!$membershipBlock->is_separate_payment) {
          $errors['is_recur'] = ts('You need to enable Separate Membership Payment when online contribution page is configured for both Membership and Recurring Contribution.');
        }
        elseif (count(array_filter($membershipTypes)) != 0) {
          $errors['is_recur'] = ts('You cannot enable both Recurring Contributions and Auto-renew memberships on the same online contribution page.');
        }
      }
    }

    // CRM-18854 Check if recurring start date is in the future.
    if (CRM_Utils_Array::value('pledge_calendar_date', $fields)) {
      if (date('Ymd') > date('Ymd', strtotime($fields['pledge_calendar_date']))) {
        $errors['pledge_calendar_date'] = ts('The recurring start date cannot be prior to the current date.');
      }
    }

    //check for the amount label (mandatory)
    if (!empty($fields['amount_block_is_active']) && empty($fields['price_set_id']) && empty($fields['amount_label'])) {
      $errors['amount_label'] = ts('Please enter the contribution amount label.');
    }
    $minAmount = CRM_Utils_Array::value('min_amount', $fields);
    $maxAmount = CRM_Utils_Array::value('max_amount', $fields);
    if (!empty($minAmount) && !empty($maxAmount)) {
      $minAmount = CRM_Utils_Rule::cleanMoney($minAmount);
      $maxAmount = CRM_Utils_Rule::cleanMoney($maxAmount);
      if ((float ) $minAmount > (float ) $maxAmount) {
        $errors['min_amount'] = ts('Minimum Amount should be less than Maximum Amount');
      }
    }

    if (isset($fields['is_pay_later'])) {
      if (empty($fields['pay_later_text'])) {
        $errors['pay_later_text'] = ts('Please enter the text for the \'pay later\' checkbox displayed on the contribution form.');
      }
      if (empty($fields['pay_later_receipt'])) {
        $errors['pay_later_receipt'] = ts('Please enter the instructions to be sent to the contributor when they choose to \'pay later\'.');
      }
    }
    else {
      if ($fields['amount_block_is_active'] && empty($fields['payment_processor'])) {
        $errors['payment_processor'] = ts('You have listed fixed contribution options or selected a price set, but no payment option has been selected. Please select at least one payment processor and/or enable the pay later option.');
      }
    }

    // don't allow price set w/ membership signup, CRM-5095
    if ($priceSetId = CRM_Utils_Array::value('price_set_id', $fields)) {
      // don't allow price set w/ membership.
      if ($hasMembershipBlk) {
        $errors['price_set_id'] = ts('You cannot enable both a Contribution Price Set and Membership Signup on the same online contribution page.');
      }
    }
    else {
      if (isset($fields['is_recur'])) {
        if (empty($fields['recur_frequency_unit'])) {
          $errors['recur_frequency_unit'] = ts('At least one recurring frequency option needs to be checked.');
        }
      }

      // validation for pledge fields.
      if (!empty($fields['is_pledge_active'])) {
        if (empty($fields['pledge_frequency_unit'])) {
          $errors['pledge_frequency_unit'] = ts('At least one pledge frequency option needs to be checked.');
        }
        if (!empty($fields['is_recur'])) {
          $errors['is_recur'] = ts('You cannot enable both Recurring Contributions AND Pledges on the same online contribution page.');
        }
      }

      // If Contribution amount section is enabled, then
      // Allow other amounts must be enabled OR the Fixed Contribution
      // Contribution options must contain at least one set of values.
      if (!empty($fields['amount_block_is_active'])) {
        if (empty($fields['is_allow_other_amount']) &&
          !$priceSetId
        ) {
          //get the values of amount block
          $values = CRM_Utils_Array::value('value', $fields);
          $isSetRow = FALSE;
          for ($i = 1; $i < self::NUM_OPTION; $i++) {
            if ((isset($values[$i]) && (strlen(trim($values[$i])) > 0))) {
              $isSetRow = TRUE;
            }
          }
          if (!$isSetRow) {
            $errors['amount_block_is_active'] = ts('If you want to enable the \'Contribution Amounts section\', you need to either \'Allow Other Amounts\' and/or enter at least one row in the \'Fixed Contribution Amounts\' table.');
          }
        }
      }
    }

    if (!empty($fields['payment_processor']) && $financialType = CRM_Contribute_BAO_Contribution::validateFinancialType($self->_defaultValues['financial_type_id'])) {
      $errors['payment_processor'] = ts("Financial Account of account relationship of 'Expense Account is' is not configured for Financial Type : ") . $financialType;
    }

    return $errors;
  }

  /**
   * Process the form.
   */
  public function postProcess() {
    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);

    //update 'is_billing_required'
    if (empty($params['is_pay_later'])) {
      $params['is_billing_required'] = 0;
    }

    if (array_key_exists('payment_processor', $params)) {
      if (array_key_exists(CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_PaymentProcessor', 'AuthNet',
          'id', 'payment_processor_type_id'
        ),
        CRM_Utils_Array::value('payment_processor', $params)
      )) {
        CRM_Core_Session::setStatus(ts(' Please note that the Authorize.net payment processor only allows recurring contributions and auto-renew memberships with payment intervals from 7-365 days or 1-12 months (i.e. not greater than 1 year).'), '', 'alert');
      }
    }

    // check for price set.
    $priceSetID = CRM_Utils_Array::value('price_set_id', $params);

    // get required fields.
    $fields = [
      'id' => $this->_id,
      'is_recur' => FALSE,
      'min_amount' => "null",
      'max_amount' => "null",
      'is_monetary' => FALSE,
      'is_pay_later' => FALSE,
      'is_billing_required' => FALSE,
      'is_recur_interval' => FALSE,
      'is_recur_installments' => FALSE,
      'recur_frequency_unit' => "null",
      'default_amount_id' => "null",
      'is_allow_other_amount' => FALSE,
      'amount_block_is_active' => FALSE,
    ];
    $resetFields = [];
    if ($priceSetID) {
      $resetFields = ['min_amount', 'max_amount', 'is_allow_other_amount'];
    }

    if (empty($params['is_recur'])) {
      $resetFields = array_merge($resetFields, ['is_recur_interval', 'recur_frequency_unit']);
    }

    foreach ($fields as $field => $defaultVal) {
      $val = CRM_Utils_Array::value($field, $params, $defaultVal);
      if (in_array($field, $resetFields)) {
        $val = $defaultVal;
      }

      if (in_array($field, [
        'min_amount',
        'max_amount',
      ])) {
        $val = CRM_Utils_Rule::cleanMoney($val);
      }

      $params[$field] = $val;
    }

    if ($params['is_recur']) {
      $params['recur_frequency_unit'] = implode(CRM_Core_DAO::VALUE_SEPARATOR,
        array_keys($params['recur_frequency_unit'])
      );
      $params['is_recur_interval'] = CRM_Utils_Array::value('is_recur_interval', $params, FALSE);
      $params['is_recur_installments'] = CRM_Utils_Array::value('is_recur_installments', $params, FALSE);
    }

    if (CRM_Utils_Array::value('adjust_recur_start_date', $params)) {
      $fieldValue = '';
      $pledgeDateFields = [
        'calendar_date' => 'pledge_calendar_date',
        'calendar_month' => 'pledge_calendar_month',
      ];
      if ($params['pledge_default_toggle'] == 'contribution_date') {
        $fieldValue = json_encode(['contribution_date' => date('m/d/Y')]);
      }
      else {
        foreach ($pledgeDateFields as $key => $pledgeDateField) {
          if (CRM_Utils_Array::value($pledgeDateField, $params) && $params['pledge_default_toggle'] == $key) {
            $fieldValue = json_encode([$key => $params[$pledgeDateField]]);
            break;
          }
        }
      }
      $params['pledge_start_date'] = $fieldValue;
    }
    else {
      $params['pledge_start_date'] = '';
      $params['adjust_recur_start_date'] = 0;
      $params['is_pledge_start_date_visible'] = 0;
      $params['is_pledge_start_date_editable'] = 0;
    }
    if (!CRM_Utils_Array::value('is_pledge_start_date_visible', $params)) {
      $params['is_pledge_start_date_visible'] = 0;
    }
    if (!CRM_Utils_Array::value('is_pledge_start_date_editable', $params)) {
      $params['is_pledge_start_date_editable'] = 0;
    }

    if (array_key_exists('payment_processor', $params) &&
      !CRM_Utils_System::isNull($params['payment_processor'])
    ) {
      $params['payment_processor'] = implode(CRM_Core_DAO::VALUE_SEPARATOR, array_keys($params['payment_processor']));
    }
    else {
      $params['payment_processor'] = 'null';
    }

    $contributionPage = CRM_Contribute_BAO_ContributionPage::create($params);
    $contributionPageID = $contributionPage->id;

    // prepare for data cleanup.
    $deleteAmountBlk = $deletePledgeBlk = $deletePriceSet = FALSE;
    if ($this->_priceSetID) {
      $deletePriceSet = TRUE;
    }
    if ($this->_pledgeBlockID) {
      $deletePledgeBlk = TRUE;
    }
    if (!empty($this->_amountBlock)) {
      $deleteAmountBlk = TRUE;
    }

    if ($contributionPageID) {

      if (!empty($params['amount_block_is_active'])) {
        // handle price set.
        if ($priceSetID) {
          // add/update price set.
          $deletePriceSet = FALSE;
          if (!empty($params['price_field_id']) || !empty($params['price_field_other'])) {
            $deleteAmountBlk = TRUE;
          }

          CRM_Price_BAO_PriceSet::addTo('civicrm_contribution_page', $contributionPageID, $priceSetID);
        }
        else {

          $deletePriceSet = FALSE;
          // process contribution amount block
          $deleteAmountBlk = FALSE;

          $labels = CRM_Utils_Array::value('label', $params);
          $values = CRM_Utils_Array::value('value', $params);
          $default = CRM_Utils_Array::value('default', $params);

          $options = [];
          for ($i = 1; $i < self::NUM_OPTION; $i++) {
            if (isset($values[$i]) &&
              (strlen(trim($values[$i])) > 0)
            ) {
              $values[$i] = $params['value'][$i] = CRM_Utils_Rule::cleanMoney(trim($values[$i]));
              $options[] = [
                'label' => trim($labels[$i]),
                'value' => $values[$i],
                'weight' => $i,
                'is_active' => 1,
                'is_default' => $default == $i,
              ];
            }
          }
          /* || !empty($params['price_field_value']) || CRM_Utils_Array::value( 'price_field_other', $params )*/
          if (!empty($options) || !empty($params['is_allow_other_amount'])) {
            $fieldParams['is_quick_config'] = 1;
            $noContriAmount = NULL;
            $usedPriceSetId = CRM_Price_BAO_PriceSet::getFor('civicrm_contribution_page', $this->_id, 3);
            if (!(!empty($params['price_field_id']) || !empty($params['price_field_other'])) && !$usedPriceSetId) {
              $pageTitle = strtolower(CRM_Utils_String::munge($this->_values['title'], '_', 245));
              $setParams['title'] = $this->_values['title'];
              if (!CRM_Core_DAO::getFieldValue('CRM_Price_BAO_PriceSet', $pageTitle, 'id', 'name')) {
                $setParams['name'] = $pageTitle;
              }
              elseif (!CRM_Core_DAO::getFieldValue('CRM_Price_BAO_PriceSet', $pageTitle . '_' . $this->_id, 'id', 'name')) {
                $setParams['name'] = $pageTitle . '_' . $this->_id;
              }
              else {
                $timeSec = explode(".", microtime(TRUE));
                $setParams['name'] = $pageTitle . '_' . date('is', $timeSec[0]) . $timeSec[1];
              }
              $setParams['is_quick_config'] = 1;
              $setParams['financial_type_id'] = CRM_Utils_Array::value('financial_type_id', $this->_values);
              $setParams['extends'] = CRM_Core_Component::getComponentID('CiviContribute');
              $priceSet = CRM_Price_BAO_PriceSet::create($setParams);
              $priceSetId = $priceSet->id;
            }
            elseif ($usedPriceSetId && empty($params['price_field_id'])) {
              $priceSetId = $usedPriceSetId;
            }
            else {
              if ($priceFieldId = CRM_Utils_Array::value('price_field_id', $params)) {
                foreach ($params['price_field_value'] as $arrayID => $fieldValueID) {
                  if (empty($params['label'][$arrayID]) && empty($params['value'][$arrayID]) && !empty($fieldValueID)) {
                    CRM_Price_BAO_PriceFieldValue::setIsActive($fieldValueID, '0');
                    unset($params['price_field_value'][$arrayID]);
                  }
                }
                if (implode('', $params['price_field_value'])) {
                  $fieldParams['id'] = CRM_Utils_Array::value('price_field_id', $params);
                  $fieldParams['option_id'] = $params['price_field_value'];
                }
                else {
                  $noContriAmount = 0;
                  CRM_Price_BAO_PriceField::setIsActive($priceFieldId, '0');
                }
              }
              else {
                $priceFieldId = CRM_Utils_Array::value('price_field_other', $params);
              }
              $priceSetId = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceField', $priceFieldId, 'price_set_id');
            }
            CRM_Price_BAO_PriceSet::addTo('civicrm_contribution_page', $this->_id, $priceSetId);
            if (!empty($options)) {
              $editedFieldParams = [
                'price_set_id' => $priceSetId,
                'name' => 'contribution_amount',
              ];
              $editedResults = [];
              $noContriAmount = 1;
              CRM_Price_BAO_PriceField::retrieve($editedFieldParams, $editedResults);
              if (empty($editedResults['id'])) {
                $fieldParams['name'] = strtolower(CRM_Utils_String::munge("Contribution Amount", '_', 245));
              }
              else {
                $fieldParams['id'] = CRM_Utils_Array::value('id', $editedResults);
              }

              $fieldParams['price_set_id'] = $priceSetId;
              $fieldParams['is_active'] = 1;
              $fieldParams['weight'] = 2;

              if (!empty($params['is_allow_other_amount'])) {
                $fieldParams['is_required'] = 0;
              }
              else {
                $fieldParams['is_required'] = 1;
              }
              $fieldParams['label'] = $params['amount_label'];
              $fieldParams['html_type'] = 'Radio';
              $fieldParams['option_label'] = $params['label'];
              $fieldParams['option_amount'] = $params['value'];
              $fieldParams['financial_type_id'] = CRM_Utils_Array::value('financial_type_id', $this->_values);
              foreach ($options as $value) {
                $fieldParams['option_weight'][$value['weight']] = $value['weight'];
              }
              $fieldParams['default_option'] = $params['default'];
              $priceField = CRM_Price_BAO_PriceField::create($fieldParams);
            }
            if (!empty($params['is_allow_other_amount']) && empty($params['price_field_other'])) {
              $editedFieldParams = [
                'price_set_id' => $priceSetId,
                'name' => 'other_amount',
              ];
              $editedResults = [];

              CRM_Price_BAO_PriceField::retrieve($editedFieldParams, $editedResults);

              if (!$priceFieldID = CRM_Utils_Array::value('id', $editedResults)) {
                $fieldParams = [
                  'name' => 'other_amount',
                  'label' => ts('Other Amount'),
                  'price_set_id' => $priceSetId,
                  'html_type' => 'Text',
                  'financial_type_id' => CRM_Utils_Array::value('financial_type_id', $this->_values),
                  'is_display_amounts' => 0,
                  'weight' => 3,
                ];
                $fieldParams['option_weight'][1] = 1;
                $fieldParams['option_amount'][1] = 1;
                if (!$noContriAmount) {
                  $fieldParams['is_required'] = 1;
                  $fieldParams['option_label'][1] = $fieldParams['label'] = $params['amount_label'];
                }
                else {
                  $fieldParams['is_required'] = 0;
                  $fieldParams['option_label'][1] = $fieldParams['label'] = ts('Other Amount');
                }

                $priceField = CRM_Price_BAO_PriceField::create($fieldParams);
              }
              else {
                if (empty($editedResults['is_active'])) {
                  $fieldParams = $editedResults;
                  if (!$noContriAmount) {
                    $priceFieldValueID = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceFieldValue', $priceFieldID, 'id', 'price_field_id');
                    CRM_Core_DAO::setFieldValue('CRM_Price_DAO_PriceFieldValue', $priceFieldValueID, 'label', $params['amount_label']);
                    $fieldParams = [
                      'is_required' => 1,
                      'label' => $params['amount_label'],
                      'id' => $priceFieldID,
                    ];
                  }
                  $fieldParams['is_active'] = 1;
                  $priceField = CRM_Price_BAO_PriceField::add($fieldParams);
                }
              }
            }
            elseif (empty($params['is_allow_other_amount']) && !empty($params['price_field_other'])) {
              CRM_Price_BAO_PriceField::setIsActive($params['price_field_other'], '0');
            }
            elseif ($priceFieldID = CRM_Utils_Array::value('price_field_other', $params)) {
              $priceFieldValueID = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceFieldValue', $priceFieldID, 'id', 'price_field_id');
              if (!$noContriAmount) {
                $fieldParams = [
                  'is_required' => 1,
                  'label' => $params['amount_label'],
                  'id' => $priceFieldID,
                ];
                CRM_Price_BAO_PriceField::add($fieldParams);
                CRM_Core_DAO::setFieldValue('CRM_Price_DAO_PriceFieldValue', $priceFieldValueID, 'label', $params['amount_label']);
              }
              else {
                CRM_Core_DAO::setFieldValue('CRM_Price_DAO_PriceField', $priceFieldID, 'is_required', 0);
                CRM_Core_DAO::setFieldValue('CRM_Price_DAO_PriceFieldValue', $priceFieldValueID, 'label', ts('Other Amount'));
              }
            }
          }

          if (!empty($params['is_pledge_active'])) {
            $deletePledgeBlk = FALSE;
            $pledgeBlockParams = [
              'entity_id' => $contributionPageID,
              'entity_table' => ts('civicrm_contribution_page'),
            ];
            if ($this->_pledgeBlockID) {
              $pledgeBlockParams['id'] = $this->_pledgeBlockID;
            }
            $pledgeBlock = [
              'pledge_frequency_unit',
              'max_reminders',
              'initial_reminder_day',
              'additional_reminder_day',
              'pledge_start_date',
              'is_pledge_start_date_visible',
              'is_pledge_start_date_editable',
            ];
            foreach ($pledgeBlock as $key) {
              $pledgeBlockParams[$key] = CRM_Utils_Array::value($key, $params);
            }
            $pledgeBlockParams['is_pledge_interval'] = CRM_Utils_Array::value('is_pledge_interval',
              $params, FALSE
            );
            $pledgeBlockParams['pledge_start_date'] = CRM_Utils_Array::value('pledge_start_date',
              $params, FALSE
            );
            // create pledge block.
            CRM_Pledge_BAO_PledgeBlock::create($pledgeBlockParams);
          }
        }
      }
      else {
        if (!empty($params['price_field_id']) || !empty($params['price_field_other'])) {
          $usedPriceSetId = CRM_Price_BAO_PriceSet::getFor('civicrm_contribution_page', $this->_id, 3);
          if ($usedPriceSetId) {
            if (!empty($params['price_field_id'])) {
              CRM_Price_BAO_PriceField::setIsActive($params['price_field_id'], '0');
            }
            if (!empty($params['price_field_other'])) {
              CRM_Price_BAO_PriceField::setIsActive($params['price_field_other'], '0');
            }
          }
          else {
            $deleteAmountBlk = TRUE;
            $deletePriceSet = TRUE;
          }
        }
      }

      // delete pledge block.
      if ($deletePledgeBlk) {
        CRM_Pledge_BAO_PledgeBlock::deletePledgeBlock($this->_pledgeBlockID);
      }

      // delete previous price set.
      if ($deletePriceSet) {
        CRM_Price_BAO_PriceSet::removeFrom('civicrm_contribution_page', $contributionPageID);
      }

      if ($deleteAmountBlk) {
        $priceField = !empty($params['price_field_id']) ? $params['price_field_id'] : CRM_Utils_Array::value('price_field_other', $params);
        if ($priceField) {
          $priceSetID = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceField', $priceField, 'price_set_id');
          CRM_Price_BAO_PriceSet::setIsQuickConfig($priceSetID, 0);
        }
      }
    }
    parent::endPostProcess();
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Amounts');
  }

}
