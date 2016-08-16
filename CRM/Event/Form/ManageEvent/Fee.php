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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * This class generates form components for Event Fees.
 */
class CRM_Event_Form_ManageEvent_Fee extends CRM_Event_Form_ManageEvent {

  /**
   * Constants for number of options for data types of multiple option.
   */
  const NUM_OPTION = 11;

  /**
   * Constants for number of discounts for the event.
   */
  const NUM_DISCOUNT = 6;

  /**
   * Page action.
   */
  public $_action;

  /**
   * In Date.
   */
  private $_inDate;

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    parent::preProcess();
  }

  /**
   * Set default values for the form.
   *
   * For edit/view mode the default values are retrieved from the database.
   */
  public function setDefaultValues() {
    $parentDefaults = parent::setDefaultValues();

    $eventId = $this->_id;
    $params = array();
    $defaults = array();
    if (isset($eventId)) {
      $params = array('id' => $eventId);
    }

    CRM_Event_BAO_Event::retrieve($params, $defaults);

    if (isset($eventId)) {
      $price_set_id = CRM_Price_BAO_PriceSet::getFor('civicrm_event', $eventId, NULL, 1);

      if ($price_set_id) {
        $defaults['price_set_id'] = $price_set_id;
      }
      else {
        $priceSetId = CRM_Price_BAO_PriceSet::getFor('civicrm_event', $eventId, NULL);
        if ($priceSetId) {
          if ($isQuick = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $priceSetId, 'is_quick_config')) {
            $this->assign('isQuick', $isQuick);
            $priceField = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceField', $priceSetId, 'id', 'price_set_id');
            $options = array();
            $priceFieldOptions = CRM_Price_BAO_PriceFieldValue::getValues($priceField, $options, 'weight', TRUE);
            $defaults['price_field_id'] = $priceField;
            $countRow = 0;
            foreach ($options as $optionId => $optionValue) {
              $countRow++;
              $defaults['value'][$countRow] = CRM_Utils_Money::format($optionValue['amount'], NULL, '%a');
              $defaults['label'][$countRow] = $optionValue['label'];
              $defaults['name'][$countRow] = $optionValue['name'];
              $defaults['weight'][$countRow] = $optionValue['weight'];
              $defaults['price_field_value'][$countRow] = $optionValue['id'];
              if ($optionValue['is_default']) {
                $defaults['default'] = $countRow;
              }
            }
          }
        }
      }
    }

    //check if discounted
    $discountedEvent = CRM_Core_BAO_Discount::getOptionGroup($this->_id, 'civicrm_event');
    if (!empty($discountedEvent)) {
      $defaults['is_discount'] = $i = 1;
      $totalLables = $maxSize = $defaultDiscounts = array();
      foreach ($discountedEvent as $optionGroupId) {
        $defaults['discount_price_set'][] = $optionGroupId;
        $name = $defaults["discount_name[$i]"] = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $optionGroupId, 'title');

        list($defaults["discount_start_date[$i]"]) = CRM_Utils_Date::setDateDefaults(CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Discount', $optionGroupId,
          'start_date', 'price_set_id'
        ));
        list($defaults["discount_end_date[$i]"]) = CRM_Utils_Date::setDateDefaults(CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Discount', $optionGroupId,
          'end_date', 'price_set_id'
        ));
        $defaultDiscounts[] = CRM_Price_BAO_PriceSet::getSetDetail($optionGroupId);
        $i++;
      }

      //avoid moving up value of lable when some labels don't
      //have a value ,fixed for CRM-3088
      $rowCount = 1;
      foreach ($defaultDiscounts as $val) {
        $discountFields = current($val);
        $discountFields = current($discountFields['fields']);

        foreach ($discountFields['options'] as $discountFieldsval) {
          $defaults['discounted_label'][$discountFieldsval['weight']] = $discountFieldsval['label'];
          $defaults['discounted_value'][$discountFieldsval['weight']][$rowCount] = CRM_Utils_Money::format($discountFieldsval['amount'], NULL, '%a');
          $defaults['discount_option_id'][$rowCount][$discountFieldsval['weight']] = $discountFieldsval['id'];
          if (!empty($discountFieldsval['is_default'])) {
            $defaults['discounted_default'] = $discountFieldsval['weight'];
          }
        }
        $rowCount++;
      }
      //CRM-12970
      ksort($defaults['discounted_value']);
      ksort($defaults['discounted_label']);
      $rowCount = 1;
      foreach ($defaults['discounted_label'] as $key => $value) {
        if ($key != $rowCount) {
          $defaults['discounted_label'][$rowCount] = $value;
          $defaults['discounted_value'][$rowCount] = $defaults['discounted_value'][$key];
          unset($defaults['discounted_value'][$key]);
          unset($defaults['discounted_label'][$key]);
          foreach ($defaults['discount_option_id'] as &$optionIds) {
            if (array_key_exists($key, $optionIds)) {
              $optionIds[$rowCount] = $optionIds[$key];
              unset($optionIds[$key]);
            }
          }
        }
        $rowCount++;
      }

      $this->set('discountSection', 1);
      $this->buildQuickForm();
    }
    elseif (!empty($defaults['label'])) {
      //if Regular Fees are present in DB and event fee page is in update mode
      $defaults['discounted_label'] = $defaults['label'];
    }
    elseif (!empty($this->_submitValues['label'])) {
      //if event is newly created, use submitted values for
      //discount labels
      if (is_array($this->_submitValues['label'])) {
        $k = 1;
        foreach ($this->_submitValues['label'] as $value) {
          if ($value) {
            $defaults['discounted_label'][$k] = $value;
            $k++;
          }
        }
      }
    }
    $defaults['id'] = $eventId;
    if (!empty($totalLables)) {
      $maxKey = count($totalLables) - 1;
      if (isset($maxKey) && !empty($totalLables[$maxKey]['value'])) {
        foreach ($totalLables[$maxKey]['value'] as $i => $v) {
          if ($totalLables[$maxKey]['amount_id'][$i] == CRM_Utils_Array::value('default_discount_fee_id', $defaults)) {
            $defaults['discounted_default'] = $i;
            break;
          }
        }
      }
    }

    if (!isset($defaults['discounted_default'])) {
      $defaults['discounted_default'] = 1;
    }

    if (!isset($defaults['is_monetary'])) {
      $defaults['is_monetary'] = 1;
    }

    if (!isset($defaults['fee_label'])) {
      $defaults['fee_label'] = ts('Event Fee(s)');
    }

    if (!isset($defaults['pay_later_text']) ||
      empty($defaults['pay_later_text'])
    ) {
      $defaults['pay_later_text'] = ts('I will send payment by check');
    }

    $this->_showHide = new CRM_Core_ShowHideBlocks();
    if (!$defaults['is_monetary']) {
      $this->_showHide->addHide('event-fees');
    }

    if (isset($defaults['price_set_id'])) {
      $this->_showHide->addHide('map-field');
    }
    $this->_showHide->addToTemplate();
    $this->assign('inDate', $this->_inDate);

    if (!empty($defaults['payment_processor'])) {
      $defaults['payment_processor'] = str_replace(CRM_Core_DAO::VALUE_SEPARATOR, ',',
        $defaults['payment_processor']
      );
    }
    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {

    $this->addYesNo('is_monetary',
      ts('Paid Event'),
      NULL,
      NULL,
      array('onclick' => "return showHideByValue('is_monetary','0','event-fees','block','radio',false);")
    );

    //add currency element.
    $this->addCurrency('currency', ts('Currency'), FALSE);

    $paymentProcessor = CRM_Core_PseudoConstant::paymentProcessor();

    $this->assign('paymentProcessor', $paymentProcessor);

    $this->addEntityRef('payment_processor', ts('Payment Processor'), array(
      'entity' => 'PaymentProcessor',
      'multiple' => TRUE,
      'select' => array('minimumInputLength' => 0),
    ));

    // financial type
    if (!CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus() ||
        (CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus() && CRM_Core_Permission::check('administer CiviCRM Financial Types'))) {
      $this->addSelect('financial_type_id');
    }
    else {
      CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($financialTypes, CRM_Core_Action::ADD);
      $this->addSelect('financial_type_id', array('context' => 'search', 'options' => $financialTypes));
    }
    // add pay later options
    $this->addElement('checkbox', 'is_pay_later', ts('Enable Pay Later option?'), NULL,
      array('onclick' => "return showHideByValue('is_pay_later','','payLaterOptions','block','radio',false);")
    );
    $this->addElement('textarea', 'pay_later_text', ts('Pay Later Label'),
      CRM_Core_DAO::getAttribute('CRM_Event_DAO_Event', 'pay_later_text'),
      FALSE
    );
    $this->add('wysiwyg', 'pay_later_receipt', ts('Pay Later Instructions'), CRM_Core_DAO::getAttribute('CRM_Event_DAO_Event', 'pay_later_receipt'));

    $this->addElement('checkbox', 'is_billing_required', ts('Billing address required'));
    $this->add('text', 'fee_label', ts('Fee Label'));

    $price = CRM_Price_BAO_PriceSet::getAssoc(FALSE, 'CiviEvent');
    if (CRM_Utils_System::isNull($price)) {
      $this->assign('price', FALSE);
    }
    else {
      $this->assign('price', TRUE);
    }
    $this->add('select', 'price_set_id', ts('Price Set'),
      array(
        '' => ts('- none -'),
      ) + $price,
      NULL, array('onchange' => "return showHideByValue('price_set_id', '', 'map-field', 'block', 'select', false);")
    );
    $default = array($this->createElement('radio', NULL, NULL, NULL, 0));
    $this->add('hidden', 'price_field_id', '', array('id' => 'price_field_id'));
    for ($i = 1; $i <= self::NUM_OPTION; $i++) {
      // label
      $this->add('text', "label[$i]", ts('Label'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'label'));
      $this->add('hidden', "price_field_value[$i]", '', array('id' => "price_field_value[$i]"));

      // value
      $this->add('text', "value[$i]", ts('Value'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'value'));
      $this->addRule("value[$i]", ts('Please enter a valid money value for this field (e.g. %1).', array(1 => CRM_Utils_Money::format('99.99', ' '))), 'money');

      // default
      $default[] = $this->createElement('radio', NULL, NULL, NULL, $i);
    }

    $this->addGroup($default, 'default');

    $this->addElement('checkbox', 'is_discount', ts('Discounts by Signup Date?'), NULL,
      array('onclick' => "warnDiscountDel(); return showHideByValue('is_discount','','discount','block','radio',false);")
    );
    $discountSection = $this->get('discountSection');

    $this->assign('discountSection', $discountSection);

    // form fields of Discount sets
    $defaultOption = array();
    $_showHide = new CRM_Core_ShowHideBlocks('', '');

    for ($i = 1; $i <= self::NUM_DISCOUNT; $i++) {
      //the show hide blocks
      $showBlocks = 'discount_' . $i;
      if ($i > 2) {
        $_showHide->addHide($showBlocks);
      }
      else {
        $_showHide->addShow($showBlocks);
      }

      //Increment by 1 of start date of previous end date.
      if (is_array($this->_submitValues) &&
        !empty($this->_submitValues['discount_name'][$i]) &&
        !empty($this->_submitValues['discount_name'][$i + 1]) &&
        isset($this->_submitValues['discount_end_date']) &&
        isset($this->_submitValues['discount_end_date'][$i]) &&
        $i < self::NUM_DISCOUNT - 1
      ) {
        $end_date = CRM_Utils_Date::processDate($this->_submitValues['discount_end_date'][$i]);
        if (!empty($this->_submitValues['discount_end_date'][$i + 1])
          && empty($this->_submitValues['discount_start_date'][$i + 1])
        ) {
          list($this->_submitValues['discount_start_date'][$i + 1]) = CRM_Utils_Date::setDateDefaults(date('Y-m-d', strtotime("+1 days $end_date")));
        }
      }
      //Decrement by 1 of end date from next start date.
      if ($i > 1 &&
        is_array($this->_submitValues) &&
        !empty($this->_submitValues['discount_name'][$i]) &&
        !empty($this->_submitValues['discount_name'][$i - 1]) &&
        isset($this->_submitValues['discount_start_date']) &&
        isset($this->_submitValues['discount_start_date'][$i])
      ) {
        $start_date = CRM_Utils_Date::processDate($this->_submitValues['discount_start_date'][$i]);
        if (!empty($this->_submitValues['discount_start_date'][$i])
          && empty($this->_submitValues['discount_end_date'][$i - 1])
        ) {
          list($this->_submitValues['discount_end_date'][$i - 1]) = CRM_Utils_Date::setDateDefaults(date('Y-m-d', strtotime("-1 days $start_date")));
        }
      }

      //discount name
      $this->add('text', 'discount_name[' . $i . ']', ts('Discount Name'),
        CRM_Core_DAO::getAttribute('CRM_Price_DAO_PriceSet', 'title')
      );

      $this->add('hidden', "discount_price_set[$i]", '', array('id' => "discount_price_set[$i]"));

      //discount start date
      $this->addDate('discount_start_date[' . $i . ']', ts('Discount Start Date'), FALSE, array('formatType' => 'activityDate'));

      //discount end date
      $this->addDate('discount_end_date[' . $i . ']', ts('Discount End Date'), FALSE, array('formatType' => 'activityDate'));
    }
    $_showHide->addToTemplate();
    $this->addElement('submit', $this->getButtonName('submit'), ts('Add Discount Set to Fee Table'),
      array('class' => 'crm-form-submit cancel')
    );

    $this->buildAmountLabel();
    parent::buildQuickForm();
  }

  /**
   * Add local and global form rules.
   */
  public function addRules() {
    $this->addFormRule(array('CRM_Event_Form_ManageEvent_Fee', 'formRule'));
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $values
   *   Posted values of the form.
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($values) {
    $errors = array();
    if (!empty($values['is_discount'])) {
      $occurDiscount = array_count_values($values['discount_name']);
      $countemptyrows = 0;
      $countemptyvalue = 0;
      for ($i = 1; $i <= self::NUM_DISCOUNT; $i++) {
        $start_date = $end_date = NULL;
        if (!empty($values['discount_name'][$i])) {
          if (!empty($values['discount_start_date'][$i])) {
            $start_date = ($values['discount_start_date'][$i]) ? CRM_Utils_Date::processDate($values['discount_start_date'][$i]) : 0;
          }
          if (!empty($values['discount_end_date'][$i])) {
            $end_date = ($values['discount_end_date'][$i]) ? CRM_Utils_Date::processDate($values['discount_end_date'][$i]) : 0;
          }

          if ($start_date && $end_date && strcmp($end_date, $start_date) < 0) {
            $errors["discount_end_date[$i]"] = ts('The discount end date cannot be prior to the start date.');
          }

          if (!$start_date && !$end_date) {
            $errors["discount_start_date[$i]"] = $errors["discount_end_date[$i]"] = ts('Please specify either start date or end date.');
          }

          if ($i > 1) {
            $end_date_1 = ($values['discount_end_date'][$i - 1]) ? CRM_Utils_Date::processDate($values['discount_end_date'][$i - 1]) : 0;
            if ($start_date && $end_date_1 && strcmp($end_date_1, $start_date) >= 0) {
              $errors["discount_start_date[$i]"] = ts('Select non-overlapping discount start date.');
            }
            elseif (!$start_date && !$end_date_1) {
              $j = $i - 1;
              $errors["discount_start_date[$i]"] = $errors["discount_end_date[$j]"] = ts('Select either of the dates.');
            }
          }

          foreach ($occurDiscount as $key => $value) {
            if ($value > 1 && $key <> '') {
              if ($key == $values['discount_name'][$i]) {
                $errors['discount_name[' . $i . ']'] = ts('%1 is already used for Discount Name.', array(1 => $key));
              }
            }
          }

          //validation for discount labels and values
          for ($index = (self::NUM_OPTION); $index > 0; $index--) {
            $label = TRUE;
            if (empty($values['discounted_label'][$index]) && !empty($values['discounted_value'][$index][$i])) {
              $label = FALSE;
              if (!$label) {
                $errors["discounted_label[{$index}]"] = ts('Label cannot be empty.');
              }
            }
            if (!empty($values['discounted_label'][$index])) {
              $duplicateIndex = CRM_Utils_Array::key($values['discounted_label'][$index], $values['discounted_label']);

              if ((!($duplicateIndex === FALSE)) && (!($duplicateIndex == $index))) {
                $errors["discounted_label[{$index}]"] = ts('Duplicate label value');
              }
            }
            if (empty($values['discounted_label'][$index]) && empty($values['discounted_value'][$index][$i])) {
              $countemptyrows++;
            }
            if (empty($values['discounted_value'][$index][$i])) {
              $countemptyvalue++;
            }
          }
          if (!empty($values['_qf_Fee_next']) && ($countemptyrows == 11 || $countemptyvalue == 11)) {
            $errors["discounted_label[1]"] = $errors["discounted_value[1][$i]"] = ts('At least one fee should be entered for your Discount Set. If you do not see the table to enter discount fees, click the "Add Discount Set to Fee Table" button.');
          }
        }
      }
    }
    if ($values['is_monetary']) {
      //check if financial type is selected
      if (!$values['financial_type_id']) {
        $errors['financial_type_id'] = ts("Please select financial type.");
      }

      //check for the event fee label (mandatory)
      if (!$values['fee_label']) {
        $errors['fee_label'] = ts('Please enter the fee label for the paid event.');
      }

      if (empty($values['price_set_id'])) {
        //check fee label and amount
        $check = 0;
        $optionKeys = array();
        foreach ($values['label'] as $key => $val) {
          if (trim($val) && trim($values['value'][$key])) {
            $optionKeys[$key] = $key;
            $check++;
          }
        }

        $default = CRM_Utils_Array::value('default', $values);
        if ($default && !in_array($default, $optionKeys)) {
          $errors['default'] = ts('Please select an appropriate option as default.');
        }

        if (!$check) {
          if (!$values['label'][1]) {
            $errors['label[1]'] = ts('Please enter a label for at least one fee level.');
          }
          if (!$values['value'][1]) {
            $errors['value[1]'] = ts('Please enter an amount for at least one fee level.');
          }
        }
      }
      if (isset($values['is_pay_later'])) {
        if (empty($values['pay_later_text'])) {
          $errors['pay_later_text'] = ts('Please enter the Pay Later prompt to be displayed on the Registration form.');
        }
        if (empty($values['pay_later_receipt'])) {
          $errors['pay_later_receipt'] = ts('Please enter the Pay Later instructions to be displayed to your users.');
        }
      }
    }
    // CRM-16189
    try {
      CRM_Financial_BAO_FinancialAccount::validateFinancialType($values['financial_type_id']);
    }
    catch (CRM_Core_Exception $e) {
      $errors['financial_type_id'] = $e->getMessage();
    }
    return empty($errors) ? TRUE : $errors;
  }

  public function buildAmountLabel() {
    $default = array();
    for ($i = 1; $i <= self::NUM_OPTION; $i++) {
      // label
      $this->add('text', "discounted_label[$i]", ts('Label'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'label'));
      // value
      for ($j = 1; $j <= self::NUM_DISCOUNT; $j++) {
        $this->add('text', "discounted_value[$i][$j]", ts('Value'), array('size' => 10));
        $this->addRule("discounted_value[$i][$j]", ts('Please enter a valid money value for this field (e.g. %1).', array(1 => CRM_Utils_Money::format('99.99', ' '))), 'money');
      }

      // default
      $default[] = $this->createElement('radio', NULL, NULL, NULL, $i);
    }

    $this->addGroup($default, 'discounted_default');
  }

  /**
   * Process the form.
   */
  public function postProcess() {
    $eventTitle = '';
    $params = $this->exportValues();

    $this->set('discountSection', 0);

    if (!empty($_POST['_qf_Fee_submit'])) {
      $this->buildAmountLabel();
      $this->set('discountSection', 2);
      return;
    }

    if (!empty($params['payment_processor'])) {
      $params['payment_processor'] = str_replace(',', CRM_Core_DAO::VALUE_SEPARATOR, $params['payment_processor']);
    }
    else {
      $params['payment_processor'] = 'null';
    }

    $params['is_pay_later'] = CRM_Utils_Array::value('is_pay_later', $params, 0);
    $params['is_billing_required'] = CRM_Utils_Array::value('is_billing_required', $params, 0);

    if ($this->_id) {

      // delete all the prior label values or discounts in the custom options table
      // and delete a price set if one exists
      //@todo note that this removes the reference from existing participants -
      // even where there is not change - redress?
      // note that a more tentative form of this is invoked by passing price_set_id as an array
      // to event.create see CRM-14069
      // @todo get all of this logic out of form layer (currently partially in BAO/api layer)
      if (CRM_Price_BAO_PriceSet::removeFrom('civicrm_event', $this->_id)) {
        CRM_Core_BAO_Discount::del($this->_id, 'civicrm_event');
      }
    }

    if ($params['is_monetary']) {
      if (!empty($params['price_set_id'])) {
        //@todo this is now being done in the event BAO if passed price_set_id as an array
        // per notes on that fn - looking at the api converting to an array
        // so calling via the api may cause this to be done in the api
        CRM_Price_BAO_PriceSet::addTo('civicrm_event', $this->_id, $params['price_set_id']);
        if (!empty($params['price_field_id'])) {
          $priceSetID = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceField', $params['price_field_id'], 'price_set_id');
          CRM_Price_BAO_PriceSet::setIsQuickConfig($priceSetID, 0);
        }
      }
      else {
        // if there are label / values, create custom options for them
        $labels = CRM_Utils_Array::value('label', $params);
        $values = CRM_Utils_Array::value('value', $params);
        $default = CRM_Utils_Array::value('default', $params);
        $options = array();
        if (!CRM_Utils_System::isNull($labels) && !CRM_Utils_System::isNull($values)) {
          for ($i = 1; $i < self::NUM_OPTION; $i++) {
            if (!empty($labels[$i]) && !CRM_Utils_System::isNull($values[$i])) {
              $options[] = array(
                'label' => trim($labels[$i]),
                'value' => CRM_Utils_Rule::cleanMoney(trim($values[$i])),
                'weight' => $i,
                'is_active' => 1,
                'is_default' => $default == $i,
              );
            }
          }
          if (!empty($options)) {
            $params['default_fee_id'] = NULL;
            if (empty($params['price_set_id'])) {
              if (empty($params['price_field_id'])) {
                $setParams['title'] = $eventTitle = ($this->_isTemplate) ? $this->_defaultValues['template_title'] : $this->_defaultValues['title'];
                $eventTitle = strtolower(CRM_Utils_String::munge($eventTitle, '_', 245));
                if (!CRM_Core_DAO::getFieldValue('CRM_Price_BAO_PriceSet', $eventTitle, 'id', 'name')) {
                  $setParams['name'] = $eventTitle;
                }
                elseif (!CRM_Core_DAO::getFieldValue('CRM_Price_BAO_PriceSet', $eventTitle . '_' . $this->_id, 'id', 'name')) {
                  $setParams['name'] = $eventTitle . '_' . $this->_id;
                }
                else {
                  $timeSec = explode('.', microtime(TRUE));
                  $setParams['name'] = $eventTitle . '_' . date('is', $timeSec[0]) . $timeSec[1];
                }
                $setParams['is_quick_config'] = 1;
                $setParams['financial_type_id'] = $params['financial_type_id'];
                $setParams['extends'] = CRM_Core_Component::getComponentID('CiviEvent');
                $priceSet = CRM_Price_BAO_PriceSet::create($setParams);

                $fieldParams['name'] = strtolower(CRM_Utils_String::munge($params['fee_label'], '_', 245));
                $fieldParams['price_set_id'] = $priceSet->id;
              }
              else {
                foreach ($params['price_field_value'] as $arrayID => $fieldValueID) {
                  if (empty($params['label'][$arrayID]) && empty($params['value'][$arrayID]) && !empty($fieldValueID)) {
                    CRM_Price_BAO_PriceFieldValue::setIsActive($fieldValueID, '0');
                    unset($params['price_field_value'][$arrayID]);
                  }
                }
                $fieldParams['id'] = CRM_Utils_Array::value('price_field_id', $params);
                $fieldParams['option_id'] = $params['price_field_value'];

                $priceSet = new CRM_Price_BAO_PriceSet();
                $priceSet->id = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceField', CRM_Utils_Array::value('price_field_id', $params), 'price_set_id');

                if ($this->_defaultValues['financial_type_id'] != $params['financial_type_id']) {
                  CRM_Core_DAO::setFieldValue('CRM_Price_DAO_PriceSet', $priceSet->id, 'financial_type_id', $params['financial_type_id']);
                }
              }
              $fieldParams['label'] = $params['fee_label'];
              $fieldParams['html_type'] = 'Radio';
              CRM_Price_BAO_PriceSet::addTo('civicrm_event', $this->_id, $priceSet->id);
              $fieldParams['option_label'] = $params['label'];
              $fieldParams['option_amount'] = $params['value'];
              $fieldParams['financial_type_id'] = $params['financial_type_id'];
              foreach ($options as $value) {
                $fieldParams['option_weight'][$value['weight']] = $value['weight'];
              }
              $fieldParams['default_option'] = $params['default'];
              $priceField = CRM_Price_BAO_PriceField::create($fieldParams);
            }
          }
        }

        $discountPriceSets = !empty($this->_defaultValues['discount_price_set']) ? $this->_defaultValues['discount_price_set'] : array();
        $discountFieldIDs = !empty($this->_defaultValues['discount_option_id']) ? $this->_defaultValues['discount_option_id'] : array();
        if (CRM_Utils_Array::value('is_discount', $params) == 1) {
          // if there are discounted set of label / values,
          // create custom options for them
          $labels = CRM_Utils_Array::value('discounted_label', $params);
          $values = CRM_Utils_Array::value('discounted_value', $params);
          $default = CRM_Utils_Array::value('discounted_default', $params);

          if (!CRM_Utils_System::isNull($labels) && !CRM_Utils_System::isNull($values)) {
            for ($j = 1; $j <= self::NUM_DISCOUNT; $j++) {
              $discountOptions = array();
              for ($i = 1; $i < self::NUM_OPTION; $i++) {
                if (!empty($labels[$i]) &&
                  !CRM_Utils_System::isNull(CRM_Utils_Array::value($j, $values[$i]))
                ) {
                  $discountOptions[] = array(
                    'label' => trim($labels[$i]),
                    'value' => CRM_Utils_Rule::cleanMoney(trim($values[$i][$j])),
                    'weight' => $i,
                    'is_active' => 1,
                    'is_default' => $default == $i,
                  );
                }
              }

              if (!empty($discountOptions)) {
                $fieldParams = array();
                $params['default_discount_fee_id'] = NULL;
                $keyCheck = $j - 1;
                $setParams = array();
                if (empty($discountPriceSets[$keyCheck])) {
                  if (!$eventTitle) {
                    $eventTitle = strtolower(CRM_Utils_String::munge($this->_defaultValues['title'], '_', 200));
                  }
                  $setParams['title'] = $params['discount_name'][$j];
                  if (!CRM_Core_DAO::getFieldValue('CRM_Price_BAO_PriceSet', $eventTitle . '_' . $params['discount_name'][$j], 'id', 'name')) {
                    $setParams['name'] = $eventTitle . '_' . $params['discount_name'][$j];
                  }
                  elseif (!CRM_Core_DAO::getFieldValue('CRM_Price_BAO_PriceSet', $eventTitle . '_' . $params['discount_name'][$j] . '_' . $this->_id, 'id', 'name')) {
                    $setParams['name'] = $eventTitle . '_' . $params['discount_name'][$j] . '_' . $this->_id;
                  }
                  else {
                    $timeSec = explode('.', microtime(TRUE));
                    $setParams['name'] = $eventTitle . '_' . $params['discount_name'][$j] . '_' . date('is', $timeSec[0]) . $timeSec[1];
                  }
                  $setParams['is_quick_config'] = 1;
                  $setParams['financial_type_id'] = $params['financial_type_id'];
                  $setParams['extends'] = CRM_Core_Component::getComponentID('CiviEvent');
                  $priceSet = CRM_Price_BAO_PriceSet::create($setParams);
                  $priceSetID = $priceSet->id;
                }
                else {
                  $priceSetID = $discountPriceSets[$j - 1];
                  $setParams = array(
                    'title' => $params['discount_name'][$j],
                    'id' => $priceSetID,
                  );
                  if ($this->_defaultValues['financial_type_id'] != $params['financial_type_id']) {
                    $setParams['financial_type_id'] = $params['financial_type_id'];
                  }
                  CRM_Price_BAO_PriceSet::create($setParams);
                  unset($discountPriceSets[$j - 1]);
                  $fieldParams['id'] = CRM_Core_DAO::getFieldValue('CRM_Price_BAO_PriceField', $priceSetID, 'id', 'price_set_id');
                }

                $fieldParams['name'] = $fieldParams['label'] = $params['fee_label'];
                $fieldParams['is_required'] = 1;
                $fieldParams['price_set_id'] = $priceSetID;
                $fieldParams['html_type'] = 'Radio';
                $fieldParams['financial_type_id'] = $params['financial_type_id'];
                foreach ($discountOptions as $value) {
                  $fieldParams['option_label'][$value['weight']] = $value['label'];
                  $fieldParams['option_amount'][$value['weight']] = $value['value'];
                  $fieldParams['option_weight'][$value['weight']] = $value['weight'];
                  if (!empty($value['is_default'])) {
                    $fieldParams['default_option'] = $value['weight'];
                  }
                  if (!empty($discountFieldIDs[$j]) && !empty($discountFieldIDs[$j][$value['weight']])) {
                    $fieldParams['option_id'][$value['weight']] = $discountFieldIDs[$j][$value['weight']];
                    unset($discountFieldIDs[$j][$value['weight']]);
                  }
                }
                //create discount priceset
                $priceField = CRM_Price_BAO_PriceField::create($fieldParams);
                if (!empty($discountFieldIDs[$j])) {
                  foreach ($discountFieldIDs[$j] as $fID) {
                    CRM_Price_BAO_PriceFieldValue::setIsActive($fID, '0');
                  }
                }

                $discountParams = array(
                  'entity_table' => 'civicrm_event',
                  'entity_id' => $this->_id,
                  'price_set_id' => $priceSetID,
                  'start_date' => CRM_Utils_Date::processDate($params['discount_start_date'][$j]),
                  'end_date' => CRM_Utils_Date::processDate($params['discount_end_date'][$j]),
                );
                CRM_Core_BAO_Discount::add($discountParams);
              }
            }
          }
        }
        if (!empty($discountPriceSets)) {
          foreach ($discountPriceSets as $setId) {
            CRM_Price_BAO_PriceSet::setIsQuickConfig($setId, 0);
          }
        }
      }
    }
    else {
      if (!empty($params['price_field_id'])) {
        $priceSetID = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceField', $params['price_field_id'], 'price_set_id');
        CRM_Price_BAO_PriceSet::setIsQuickConfig($priceSetID, 0);
      }
      $params['financial_type_id'] = '';
      $params['is_pay_later'] = 0;
      $params['is_billing_required'] = 0;
    }

    //update 'is_billing_required'
    if (empty($params['is_pay_later'])) {
      $params['is_billing_required'] = FALSE;
    }

    //update events table
    $params['id'] = $this->_id;
    // skip update of financial type in price set
    $params['skipFinancialType'] = TRUE;
    CRM_Event_BAO_Event::add($params);

    // Update tab "disabled" css class
    $this->ajaxResponse['tabValid'] = !empty($params['is_monetary']);
    parent::endPostProcess();
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Event Fees');
  }

}
