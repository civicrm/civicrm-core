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

use Civi\Api4\PriceField;

/**
 * Business objects for managing price fields.
 *
 */
class CRM_Price_BAO_PriceField extends CRM_Price_DAO_PriceField {

  protected $_options;

  /**
   * List of visibility option ID's, of the form name => ID
   *
   * @var array
   */
  private static $visibilityOptionsKeys;

  /**
   * Create or update a PriceField.
   *
   * @param array $params
   * @return CRM_Price_DAO_PriceField
   */
  public static function add($params) {
    return self::writeRecord($params);
  }

  /**
   * Takes an associative array and creates a price field object.
   *
   * This function is invoked from within the web form layer and also from the api layer
   *
   * @param array $params
   *   (reference) an assoc array of name/value pairs.
   *
   * @return CRM_Price_DAO_PriceField
   *
   * @throws \CRM_Core_Exception
   */
  public static function create(&$params) {
    if (empty($params['id']) && empty($params['name'])) {
      $params['name'] = strtolower(CRM_Utils_String::munge($params['label'], '_', 242));
    }
    $transaction = new CRM_Core_Transaction();

    $priceField = self::add($params);

    if (is_a($priceField, 'CRM_Core_Error')) {
      $transaction->rollback();
      return $priceField;
    }

    if (!empty($params['id']) && empty($priceField->html_type)) {
      $priceField->html_type = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceField', $params['id'], 'html_type');
    }
    $optionsIds = [];
    $maxIndex = CRM_Price_Form_Field::NUM_OPTION;
    if ($priceField->html_type == 'Text') {
      $maxIndex = 1;
      $fieldOptions = civicrm_api3('price_field_value', 'get', [
        'price_field_id' => $priceField->id,
        'sequential' => 1,
      ]);
      foreach ($fieldOptions['values'] as $option) {
        $optionsIds['id'] = $option['id'];
        $params['option_id'] = [1 => $option['id']];
        // CRM-19741 If we are dealing with price fields that are Text only set the field value label to match
        if (!empty($params['id']) && $priceField->label != $option['label']) {
          $fieldValue = new CRM_Price_DAO_PriceFieldValue();
          $fieldValue->label = $priceField->label;
          $fieldValue->id = $option['id'];
          $fieldValue->save();
        }
      }
    }
    $defaultArray = [];
    //html type would be empty in update scenario not sure what would happen ...
    if (!empty($params['html_type']) && $params['html_type'] == 'CheckBox' && isset($params['default_checkbox_option'])) {
      $tempArray = array_keys($params['default_checkbox_option']);
      foreach ($tempArray as $v) {
        if ($params['option_amount'][$v]) {
          $defaultArray[$v] = 1;
        }
      }
    }
    else {
      if (!empty($params['default_option'])) {
        $defaultArray[$params['default_option']] = 1;
      }
    }

    for ($index = 1; $index <= $maxIndex; $index++) {
      if (array_key_exists('option_amount', $params) &&
        array_key_exists($index, $params['option_amount']) &&
        (CRM_Utils_Array::value($index, CRM_Utils_Array::value('option_label', $params)) || !empty($params['is_quick_config'])) &&
        !CRM_Utils_System::isNull($params['option_amount'][$index])
      ) {
        $options = [
          'price_field_id' => $priceField->id,
          'label' => trim($params['option_label'][$index]),
          'name' => CRM_Utils_String::munge($params['option_label'][$index], '_', 64),
          'amount' => trim($params['option_amount'][$index]),
          'count' => CRM_Utils_Array::value($index, CRM_Utils_Array::value('option_count', $params), NULL),
          'max_value' => CRM_Utils_Array::value($index, CRM_Utils_Array::value('option_max_value', $params), NULL),
          'description' => CRM_Utils_Array::value($index, CRM_Utils_Array::value('option_description', $params), NULL),
          'membership_type_id' => CRM_Utils_Array::value($index, CRM_Utils_Array::value('membership_type_id', $params), NULL),
          'weight' => $params['option_weight'][$index],
          'is_active' => 1,
          'is_default' => !empty($defaultArray[$params['option_weight'][$index]]) ? $defaultArray[$params['option_weight'][$index]] : 0,
          'membership_num_terms' => NULL,
          'non_deductible_amount' => $params['non_deductible_amount'] ?? NULL,
          'visibility_id' => CRM_Utils_Array::value($index, CRM_Utils_Array::value('option_visibility_id', $params), self::getVisibilityOptionID('public')),
        ];

        if ($options['membership_type_id']) {
          $options['membership_num_terms'] = CRM_Utils_Array::value($index, CRM_Utils_Array::value('membership_num_terms', $params), 1);
          $options['is_default'] = !empty($defaultArray[$params['membership_type_id'][$index]]) ? $defaultArray[$params['membership_type_id'][$index]] : 0;
        }

        if (CRM_Utils_Array::value($index, CRM_Utils_Array::value('option_financial_type_id', $params))) {
          $options['financial_type_id'] = $params['option_financial_type_id'][$index];
        }
        elseif (!empty($params['financial_type_id'])) {
          $options['financial_type_id'] = $params['financial_type_id'];
        }
        if ($opIds = CRM_Utils_Array::value('option_id', $params)) {
          if ($opId = CRM_Utils_Array::value($index, $opIds)) {
            $options['id'] = $opId;
          }
          else {
            $options['id'] = NULL;
          }
        }
        try {
          CRM_Price_BAO_PriceFieldValue::create($options, $optionsIds);
        }
        catch (Exception $e) {
          $transaction->rollback();
          throw new CRM_Core_Exception($e->getMessage());
        }
      }
      elseif (!empty($optionsIds) && !empty($optionsIds['id'])) {
        $optionsLoad = civicrm_api3('price_field_value', 'get', ['id' => $optionsIds['id']]);
        $options = $optionsLoad['values'][$optionsIds['id']];
        $options['is_active'] = CRM_Utils_Array::value('is_active', $params, 1);
        try {
          CRM_Price_BAO_PriceFieldValue::create($options, $optionsIds);
        }
        catch (Exception $e) {
          $transaction->rollback();
          throw new CRM_Core_Exception($e->getMessage());
        }
      }
    }

    $transaction->commit();
    Civi::cache('metadata')->flush();
    return $priceField;
  }

  /**
   * Retrieve DB object and copy to defaults array.
   *
   * @param array $params
   *   Array of criteria values.
   * @param array $defaults
   *   Array to be populated with found values.
   *
   * @return self|null
   *   The DAO object, if found.
   *
   * @deprecated
   */
  public static function retrieve($params, &$defaults) {
    return self::commonRetrieve(self::class, $params, $defaults);
  }

  /**
   * Update the is_active flag in the db.
   *
   * @param int $id
   *   Id of the database record.
   * @param bool $is_active
   *   Value we want to set the is_active field.
   *
   * @return bool
   *   true if we found and updated the object, else false
   */
  public static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Price_DAO_PriceField', $id, 'is_active', $is_active);
  }

  /**
   * Freeze form if the event is full.
   *
   * @param $element
   * @param $fieldOptions
   *
   * @return null
   */
  public static function freezeIfEnabled(&$element, $fieldOptions) {
    if (!empty($fieldOptions['is_full'])) {
      $element->freeze();
    }
    return NULL;
  }

  /**
   * Get the field title.
   *
   * @param int $id
   *   Id of field.
   *
   * @return string
   *   name
   *
   */
  public static function getTitle($id) {
    return CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceField', $id, 'label');
  }

  /**
   * This function for building custom fields.
   *
   * @param CRM_Core_Form $qf
   *   Form object (reference).
   * @param string $elementName
   *   Name of the custom field.
   * @param int $fieldId
   * @param bool $inactiveNeeded
   * @param bool $useRequired
   *   True if required else false.
   * @param string $label
   *   Label for custom field.
   *
   * @param null $fieldOptions
   * @param array $freezeOptions
   *
   * @return null
   */
  public static function addQuickFormElement(
    &$qf,
    $elementName,
    $fieldId,
    $inactiveNeeded,
    $useRequired = TRUE,
    $label = NULL,
    $fieldOptions = NULL,
    $freezeOptions = []
  ) {

    $field = new CRM_Price_DAO_PriceField();
    $field->id = $fieldId;
    if (!$field->find(TRUE)) {
      /* FIXME: failure! */
      return NULL;
    }

    $is_pay_later = 0;
    if (isset($qf->_mode) && empty($qf->_mode)) {
      $is_pay_later = 1;
    }
    elseif (isset($qf->_values)) {
      $is_pay_later = $qf->_values['is_pay_later'] ?? NULL;
    }

    $otherAmount = $qf->get('values');
    $config = CRM_Core_Config::singleton();
    $currencySymbol = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_Currency', $config->defaultCurrency, 'symbol', 'name');
    $qf->assign('currencySymbol', $currencySymbol);
    $qf->assign('currency', $config->defaultCurrency);
    // get currency name for price field and option attributes
    $currencyName = $config->defaultCurrency;

    if (!isset($label)) {
      $label = (!empty($qf->_membershipBlock) && $field->name == 'contribution_amount') ? ts('Additional Contribution') : $field->label;
    }

    if ($field->name === 'contribution_amount') {
      $qf->_contributionAmount = 1;
    }

    if (isset($qf->_online) && $qf->_online) {
      $useRequired = FALSE;
    }

    $customOption = $fieldOptions;
    if (!is_array($customOption)) {
      $customOption = CRM_Price_BAO_PriceField::getOptions($field->id, $inactiveNeeded);
    }

    //use value field.
    $valueFieldName = 'amount';
    $separator = '|';
    $taxTerm = Civi::settings()->get('tax_term');
    $displayOpt = Civi::settings()->get('tax_display_settings');
    $invoicing = Civi::settings()->get('invoicing');
    switch ($field->html_type) {
      case 'Text':
        $optionKey = key($customOption);
        $count = CRM_Utils_Array::value('count', $customOption[$optionKey], '');
        $max_value = CRM_Utils_Array::value('max_value', $customOption[$optionKey], '');
        $taxAmount = $customOption[$optionKey]['tax_amount'] ?? NULL;
        if (isset($taxAmount) && $displayOpt && $invoicing) {
          $qf->assign('displayOpt', $displayOpt);
          $qf->assign('taxTerm', $taxTerm);
          $qf->assign('invoicing', $invoicing);
        }
        $priceVal = implode($separator, [
          $customOption[$optionKey][$valueFieldName] + $taxAmount,
          $count,
          $max_value,
        ]);

        $extra = [];
        if (!empty($qf->_membershipBlock) && !empty($qf->_quickConfig) && $field->name == 'other_amount' && empty($qf->_contributionAmount)) {
          $useRequired = 0;
        }
        elseif (!empty($fieldOptions[$optionKey]['label'])) {
          //check for label.
          $label = $fieldOptions[$optionKey]['label'];
          if (!empty($qf->_quickConfig) && !empty($qf->_contributionAmount) && strtolower($fieldOptions[$optionKey]['name']) == 'other_amount') {
            $label .= '  ' . $currencySymbol;
            $qf->assign('priceset', $elementName);
            $extra = [
              'onclick' => 'useAmountOther();',
              'autocomplete' => 'off',
            ];
          }
        }

        $element = &$qf->add('text', $elementName, $label,
          array_merge($extra,
            [
              'price' => json_encode([$optionKey, $priceVal]),
              'size' => '4',
            ]
          ),
          $useRequired && $field->is_required
        );
        if ($is_pay_later) {
          $qf->add('text', 'txt-' . $elementName, $label, ['size' => '4']);
        }

        // CRM-6902 - Add "max" option for a price set field
        if (in_array($optionKey, $freezeOptions)) {
          self::freezeIfEnabled($element, $fieldOptions[$optionKey]);
          // CRM-14696 - Improve display for sold out price set options
          $element->setLabel($label . '&nbsp;<span class="sold-out-option">' . ts('Sold out') . '</span>');
        }

        //CRM-10117
        if (!empty($qf->_quickConfig)) {
          $message = ts('Please enter a valid amount.');
          $type = 'money';
        }
        else {
          $message = ts('%1 must be a number (with or without decimal point).', [1 => $label]);
          $type = 'numeric';
        }
        // integers will have numeric rule applied to them.
        $qf->addRule($elementName, $message, $type);
        break;

      case 'Radio':
        $choice = [];

        if (!empty($qf->_quickConfig) && !empty($qf->_contributionAmount)) {
          $qf->assign('contriPriceset', $elementName);
        }

        foreach ($customOption as $opId => $opt) {
          $preHelpText = $postHelpText = '';
          $opt['label'] = '<span class="crm-price-amount-label">' . $opt['label'] . '</span>';
          if (!empty($opt['help_pre'])) {
            $preHelpText = '<span class="crm-price-amount-help-pre description">' . $opt['help_pre'] . '</span><span class="crm-price-amount-help-pre-separator">:&nbsp;</span>';
          }
          if (!empty($opt['help_post'])) {
            $postHelpText = '<span class="crm-price-amount-help-post-separator">:&nbsp;</span><span class="crm-price-amount-help-post description">' . $opt['help_post'] . '</span>';
          }

          $taxAmount = $opt['tax_amount'] ?? NULL;
          if ($field->is_display_amounts) {
            $opt['label'] = !empty($opt['label']) ? $opt['label'] . '<span class="crm-price-amount-label-separator">&nbsp;-&nbsp;</span>' : '';
            if (isset($taxAmount) && $invoicing) {
              $opt['label'] = $opt['label'] . self::getTaxLabel($opt, $valueFieldName);
            }
            else {
              $opt['label'] = $opt['label'] . '<span class="crm-price-amount-amount">' . CRM_Utils_Money::format($opt[$valueFieldName]) . '</span>';
            }
          }

          $opt['label'] = $preHelpText . $opt['label'] . $postHelpText;
          $count = CRM_Utils_Array::value('count', $opt, '');
          $max_value = CRM_Utils_Array::value('max_value', $opt, '');
          $priceVal = implode($separator, [$opt[$valueFieldName] + $taxAmount, $count, $max_value]);
          if (isset($opt['visibility_id'])) {
            $visibility_id = $opt['visibility_id'];
          }
          else {
            $visibility_id = self::getVisibilityOptionID('public');
          }
          $extra = [
            'price' => json_encode([$elementName, $priceVal]),
            'data-amount' => $opt[$valueFieldName],
            'data-currency' => $currencyName,
            'data-price-field-values' => json_encode($customOption),
            'visibility' => $visibility_id,
          ];
          if (!empty($qf->_quickConfig) && $field->name == 'contribution_amount') {
            $extra += ['onclick' => 'clearAmountOther();'];
          }
          if ($field->name == 'membership_amount') {
            $extra += [
              'onclick' => "return showHideAutoRenew({$opt['membership_type_id']});",
              'membership-type' => $opt['membership_type_id'],
            ];
            $qf->assign('membershipFieldID', $field->id);
          }

          $choice[$opt['id']] = $opt['label'];
          $choiceAttrs[$opt['id']] = $extra;
          if ($is_pay_later) {
            $qf->add('text', 'txt-' . $elementName, $label, ['size' => '4']);
          }
        }
        if (!empty($qf->_membershipBlock) && $field->name == 'contribution_amount') {
          $choice['-1'] = ts('No thank you');
          $choiceAttrs['-1'] = [
            'price' => json_encode([$elementName, '0|0']),
            'data-currency' => $currencyName,
            'onclick' => 'clearAmountOther();',
          ];
        }

        if (!$field->is_required) {
          // add "none" option
          if (!empty($otherAmount['is_allow_other_amount']) && $field->name == 'contribution_amount') {
            $none = ts('Other Amount');
          }
          elseif (!empty($qf->_membershipBlock) && empty($qf->_membershipBlock['is_required']) && $field->name == 'membership_amount') {
            $none = ts('No thank you');
          }
          else {
            $none = ts('- none -');
          }

          $choice['0'] = $none;
          $choiceAttrs['0'] = ['price' => json_encode([$elementName, '0'])];
        }

        $element = &$qf->addRadio($elementName, $label, $choice, [], NULL, FALSE, $choiceAttrs);
        foreach ($element->getElements() as $radioElement) {
          // CRM-6902 - Add "max" option for a price set field
          if (in_array($radioElement->getValue(), $freezeOptions)) {
            self::freezeIfEnabled($radioElement, $customOption[$radioElement->getValue()]);
            // CRM-14696 - Improve display for sold out price set options
            $radioElement->setText('<span class="sold-out-option">' . $radioElement->getText() . '&nbsp;(' . ts('Sold out') . ')</span>');
          }
        }

        // make contribution field required for quick config when membership block is enabled
        if (($field->name == 'membership_amount' || $field->name == 'contribution_amount')
          && !empty($qf->_membershipBlock) && !$field->is_required
        ) {
          $useRequired = $field->is_required = TRUE;
        }

        if ($useRequired && $field->is_required) {
          $qf->addRule($elementName, ts('%1 is a required field.', [1 => $label]), 'required');
        }
        break;

      case 'Select':
        $selectOption = $allowedOptions = $priceVal = [];

        foreach ($customOption as $opt) {
          $taxAmount = $opt['tax_amount'] ?? NULL;
          $count = CRM_Utils_Array::value('count', $opt, '');
          $max_value = CRM_Utils_Array::value('max_value', $opt, '');

          $preHelpText = $postHelpText = '';
          if (!empty($opt['help_pre'])) {
            $preHelpText = $opt['help_pre'] . ':&nbsp;';
          }
          if (!empty($opt['help_post'])) {
            $postHelpText = ':&nbsp;' . $opt['help_post'];
          }

          $taxAmount = $opt['tax_amount'] ?? NULL;
          if ($field->is_display_amounts) {
            $opt['label'] = !empty($opt['label']) ? $opt['label'] . '&nbsp;-&nbsp;' : '';
            if (isset($taxAmount) && $invoicing) {
              $opt['label'] = $opt['label'] . self::getTaxLabel($opt, $valueFieldName);
            }
            else {
              $opt['label'] = $opt['label'] . CRM_Utils_Money::format($opt[$valueFieldName]);
            }
          }

          $opt['label'] = $preHelpText . $opt['label'] . $postHelpText;

          $priceVal[$opt['id']] = implode($separator, [$opt[$valueFieldName] + $taxAmount, $count, $max_value]);

          if (!in_array($opt['id'], $freezeOptions)) {
            $allowedOptions[] = $opt['id'];
          }
          // CRM-14696 - Improve display for sold out price set options
          else {
            $opt['id'] = 'crm_disabled_opt-' . $opt['id'];
            $opt['label'] = $opt['label'] . ' (' . ts('Sold out') . ')';
          }

          $selectOption[$opt['id']] = $opt['label'];

          if ($is_pay_later) {
            $qf->add('text', 'txt-' . $elementName, $label, ['size' => '4']);
          }
        }
        if (isset($opt['visibility_id'])) {
          $visibility_id = $opt['visibility_id'];
        }
        else {
          $visibility_id = self::getVisibilityOptionID('public');
        }

        $class = '';
        $maxlen = max(array_map('strlen', $selectOption));
        if ($maxlen > 25) {
          $class = ' huge';
        }
        if ($maxlen > 40) {
          $class = ' huge40';
        }

        $element = &$qf->add('select', $elementName, $label, $selectOption, $useRequired && $field->is_required, [
          'placeholder' => ts('- select %1 -', [1 => $label]),
          'price' => json_encode($priceVal),
          'class' => 'crm-select2' . $class,
          'data-price-field-values' => json_encode($customOption),
        ]);

        // CRM-6902 - Add "max" option for a price set field
        $button = substr($qf->controller->getButtonName(), -4);
        if (!empty($freezeOptions) && $button != 'skip') {
          $qf->addRule($elementName, ts('Sorry, this option is currently sold out.'), 'regex', "/" . implode('|', $allowedOptions) . "/");
        }
        break;

      case 'CheckBox':

        $check = [];
        foreach ($customOption as $opId => $opt) {
          $taxAmount = $opt['tax_amount'] ?? NULL;
          $count = CRM_Utils_Array::value('count', $opt, '');
          $max_value = CRM_Utils_Array::value('max_value', $opt, '');

          $preHelpText = $postHelpText = '';
          $opt['label'] = '<span class="crm-price-amount-label">' . $opt['label'] . '</span>';
          if (!empty($opt['help_pre'])) {
            $preHelpText = '<span class="crm-price-amount-help-pre description">' . $opt['help_pre'] . '</span><span class="crm-price-amount-help-pre-separator">:&nbsp;</span>';
          }
          if (!empty($opt['help_post'])) {
            $postHelpText = '<span class="crm-price-amount-help-post-separator">:&nbsp;</span><span class="crm-price-amount-help-post description">' . $opt['help_post'] . '</span>';
          }

          $taxAmount = $opt['tax_amount'] ?? NULL;
          if ($field->is_display_amounts) {
            $opt['label'] = !empty($opt['label']) ? $opt['label'] . '<span class="crm-price-amount-label-separator">&nbsp;-&nbsp;</span>' : '';
            if (isset($taxAmount) && $invoicing) {
              $opt['label'] = $opt['label'] . self::getTaxLabel($opt, $valueFieldName);
            }
            else {
              $opt['label'] = $opt['label'] . '<span class="crm-price-amount-amount">' . CRM_Utils_Money::format($opt[$valueFieldName]) . '</span>';
            }
          }

          $opt['label'] = $preHelpText . $opt['label'] . $postHelpText;

          $priceVal = implode($separator, [$opt[$valueFieldName] + $taxAmount, $count, $max_value]);
          $check[$opId] = &$qf->createElement('checkbox', $opt['id'], NULL, $opt['label'],
            [
              'price' => json_encode([$opt['id'], $priceVal]),
              'data-amount' => $opt[$valueFieldName],
              'data-currency' => $currencyName,
              'visibility' => $opt['visibility_id'],
            ]
          );
          if ($is_pay_later) {
            $txtcheck[$opId] =& $qf->createElement('text', $opId, $opt['label'], ['size' => '4']);
            $qf->addGroup($txtcheck, 'txt-' . $elementName, $label);
          }
          // CRM-6902 - Add "max" option for a price set field
          if (in_array($opId, $freezeOptions)) {
            self::freezeIfEnabled($check[$opId], $customOption[$opId]);
            // CRM-14696 - Improve display for sold out price set options
            $check[$opId]->setText('<span class="sold-out-option">' . $check[$opId]->getText() . '&nbsp;(' . ts('Sold out') . ')</span>');
          }
        }
        $element = &$qf->addGroup($check, $elementName, $label);
        if ($useRequired && $field->is_required) {
          $qf->addRule($elementName, ts('%1 is a required field.', [1 => $label]), 'required');
        }
        break;
    }
    if (isset($qf->_online) && $qf->_online) {
      $element->freeze();
    }
  }

  /**
   * Retrieve a list of options for the specified field.
   *
   * @param int $fieldId
   *   Price field ID.
   * @param bool $inactiveNeeded
   *   Include inactive options.
   * @param bool $reset
   *   Discard stored values.
   * @param bool $isDefaultContributionPriceSet
   *   Discard tax amount calculation for price set = default_contribution_amount.
   *
   * @return array
   *   array of options
   */
  public static function getOptions($fieldId, $inactiveNeeded = FALSE, $reset = FALSE, $isDefaultContributionPriceSet = FALSE) {
    if ($reset || !isset(Civi::$statics[__CLASS__]['priceOptions'])) {
      Civi::$statics[__CLASS__]['priceOptions'] = [];
      // This would happen if the function was only called to clear the cache.
      if (empty($fieldId)) {
        return [];
      }
    }

    if (empty(Civi::$statics[__CLASS__]['priceOptions'][$fieldId])) {
      $values = $options = [];
      CRM_Price_BAO_PriceFieldValue::getValues($fieldId, $values, 'weight', !$inactiveNeeded);
      $options[$fieldId] = $values;
      $taxRates = CRM_Core_PseudoConstant::getTaxRates();

      // ToDo - Code for Hook Invoke

      foreach ($options[$fieldId] as $priceFieldId => $priceFieldValues) {
        if (isset($priceFieldValues['financial_type_id']) && array_key_exists($priceFieldValues['financial_type_id'], $taxRates) && !$isDefaultContributionPriceSet) {
          $options[$fieldId][$priceFieldId]['tax_rate'] = $taxRates[$priceFieldValues['financial_type_id']];
          $taxAmount = CRM_Contribute_BAO_Contribution_Utils::calculateTaxAmount($priceFieldValues['amount'], $options[$fieldId][$priceFieldId]['tax_rate']);
          $options[$fieldId][$priceFieldId]['tax_amount'] = $taxAmount['tax_amount'];
        }
      }
      Civi::$statics[__CLASS__]['priceOptions'][$fieldId] = $options[$fieldId];
    }

    return Civi::$statics[__CLASS__]['priceOptions'][$fieldId];
  }

  /**
   * @param $optionLabel
   * @param int $fid
   *
   * @return mixed
   */
  public static function getOptionId($optionLabel, $fid) {
    if (!$optionLabel || !$fid) {
      return;
    }

    $optionGroupName = "civicrm_price_field.amount.{$fid}";

    $query = "
SELECT
        option_value.id as id
FROM
        civicrm_option_value option_value,
        civicrm_option_group option_group
WHERE
        option_group.name = %1
    AND option_group.id = option_value.option_group_id
    AND option_value.label = %2";

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [$optionGroupName, 'String'],
      2 => [$optionLabel, 'String'],
    ]);

    while ($dao->fetch()) {
      return $dao->id;
    }
  }

  /**
   * Delete the price set field.
   *
   * @param int $id
   *   Field Id.
   *
   * @return bool
   *
   */
  public static function deleteField($id) {
    $field = new CRM_Price_DAO_PriceField();
    $field->id = $id;

    if ($field->find(TRUE)) {
      // delete the options for this field
      CRM_Price_BAO_PriceFieldValue::deleteValues($id);

      // reorder the weight before delete
      $fieldValues = ['price_set_id' => $field->price_set_id];

      CRM_Utils_Weight::delWeight('CRM_Price_DAO_PriceField', $field->id, $fieldValues);

      // now delete the field
      return $field->delete();
    }

    return NULL;
  }

  /**
   * @return array
   */
  public static function &htmlTypes() {
    static $htmlTypes = NULL;
    if (!$htmlTypes) {
      $htmlTypes = [
        'Text' => ts('Text / Numeric Quantity'),
        'Select' => ts('Select'),
        'Radio' => ts('Radio'),
        'CheckBox' => ts('CheckBox'),
      ];
    }
    return $htmlTypes;
  }

  /**
   * Validate the priceset.
   *
   * @param int $priceSetId
   *   , array $fields.
   *
   * retrun the error string
   *
   * @param $fields
   * @param $error
   * @param bool $allowNoneSelection
   *
   */
  public static function priceSetValidation($priceSetId, $fields, &$error, $allowNoneSelection = FALSE) {
    // check for at least one positive
    // amount price field should be selected.
    $priceField = new CRM_Price_DAO_PriceField();
    $priceField->price_set_id = $priceSetId;
    $priceField->find();

    $priceFields = [];

    if ($allowNoneSelection) {
      $noneSelectedPriceFields = [];
    }

    while ($priceField->fetch()) {
      $key = "price_{$priceField->id}";

      if ($allowNoneSelection) {
        if (array_key_exists($key, $fields)) {
          if ($fields[$key] == 0 && !$priceField->is_required) {
            $noneSelectedPriceFields[] = $priceField->id;
          }
        }
      }

      if (!empty($fields[$key])) {
        $priceFields[$priceField->id] = $fields[$key];
      }
    }

    if (!empty($priceFields)) {
      // we should has to have positive amount.
      $sql = "
SELECT  id, html_type
FROM  civicrm_price_field
WHERE  id IN (" . implode(',', array_keys($priceFields)) . ')';
      $fieldDAO = CRM_Core_DAO::executeQuery($sql);
      $htmlTypes = [];
      while ($fieldDAO->fetch()) {
        $htmlTypes[$fieldDAO->id] = $fieldDAO->html_type;
      }

      $selectedAmounts = [];

      foreach ($htmlTypes as $fieldId => $type) {
        $options = [];
        CRM_Price_BAO_PriceFieldValue::getValues($fieldId, $options);

        if (empty($options)) {
          continue;
        }

        if ($type == 'Text') {
          foreach ($options as $opId => $option) {
            $selectedAmounts[$opId] = $priceFields[$fieldId] * $option['amount'];
            break;
          }
        }
        elseif (is_array($fields["price_{$fieldId}"])) {
          foreach (array_keys($fields["price_{$fieldId}"]) as $opId) {
            $selectedAmounts[$opId] = $options[$opId]['amount'];
          }
        }
        elseif (array_key_exists($fields["price_{$fieldId}"], $options)) {
          $selectedAmounts[$fields["price_{$fieldId}"]] = $options[$fields["price_{$fieldId}"]]['amount'];
        }
      }

      list($componentName) = explode(':', $fields['_qf_default']);
      // now we have all selected amount in hand.
      $totalAmount = array_sum($selectedAmounts);
      // The form offers a field to enter the amount paid. This may differ from the amount that is due to complete the purchase
      $totalPaymentAmountEnteredOnForm = CRM_Utils_Array::value('total_amount', $fields);
      if ($totalAmount < 0) {
        $error['_qf_default'] = ts('%1 amount can not be less than zero. Please select the options accordingly.', [1 => $componentName]);
      }
      elseif ($totalAmount > 0 &&
      // if total amount is equal to all selected amount in hand
        $totalPaymentAmountEnteredOnForm >= $totalAmount &&
        (CRM_Utils_Array::value('contribution_status_id', $fields) == CRM_Core_PseudoConstant::getKey('CRM_Contribute_DAO_Contribution', 'contribution_status_id', 'Partially paid'))
      ) {
        $error['total_amount'] = ts('You have specified the status Partially Paid but have entered an amount that equals or exceeds the amount due. Please adjust the status of the payment or the amount');
      }
    }
    else {
      if ($allowNoneSelection) {
        if (empty($noneSelectedPriceFields)) {
          $error['_qf_default'] = ts('Please select at least one option from price set.');
        }
      }
      else {
        $error['_qf_default'] = ts('Please select at least one option from price set.');
      }
    }
  }

  /**
   * Generate the label for price fields based on tax display setting option on CiviContribute Component Settings page.
   *
   * @param array $opt
   * @param string $valueFieldName
   *   Amount.
   * @param string|null $currency
   *
   * @return string
   *   Tax label for custom field.
   */
  public static function getTaxLabel($opt, $valueFieldName, $currency = NULL) {
    $taxTerm = Civi::settings()->get('tax_term');
    $displayOpt = Civi::settings()->get('tax_display_settings');
    if ($displayOpt === 'Do_not_show') {
      $label = CRM_Utils_Money::format($opt[$valueFieldName] + $opt['tax_amount'], $currency);
    }
    elseif ($displayOpt === 'Inclusive') {
      $label = CRM_Utils_Money::format($opt[$valueFieldName] + $opt['tax_amount'], $currency);
      $label .= '<span class="crm-price-amount-tax"> ' . ts('(includes %1 of %2)', [1 => $taxTerm, 2 => CRM_Utils_Money::format($opt['tax_amount'], $currency)]) . '</span>';
    }
    else {
      $label = CRM_Utils_Money::format($opt[$valueFieldName], $currency);
      $label .= '<span class="crm-price-amount-tax"> + ' . CRM_Utils_Money::format($opt['tax_amount'], $currency) . ' ' . $taxTerm . '</span>';
    }

    return $label;
  }

  /**
   * Given the name of a visibility option, returns its ID.
   *
   * @param string $visibilityName
   *
   * @return int
   */
  public static function getVisibilityOptionID($visibilityName) {

    if (!isset(self::$visibilityOptionsKeys)) {
      self::$visibilityOptionsKeys = CRM_Core_PseudoConstant::get('CRM_Price_BAO_PriceField', 'visibility_id', [
        'labelColumn' => 'name',
        'flip' => TRUE,
      ]);
    }

    if (isset(self::$visibilityOptionsKeys[$visibilityName])) {
      return self::$visibilityOptionsKeys[$visibilityName];
    }

    return 0;
  }

  /**
   * Get a specific price field (leveraging the cache).
   *
   * @param int $id
   *
   * @return array
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  public static function getPriceField(int $id): array {
    $cacheString = __CLASS__ . __FUNCTION__ . $id . CRM_Core_Config::domainID() . '_' . CRM_Core_I18n::getLocale();
    if (!Civi::cache('metadata')->has($cacheString)) {
      $field = PriceField::get(FALSE)->addWhere('id', '=', $id)->execute()->first();
      Civi::cache('metadata')->set($cacheString, $field);
    }
    return Civi::cache('metadata')->get($cacheString);
  }

}
