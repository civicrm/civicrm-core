<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 5                                                  |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 * $Id$
 *
 */

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
   * Takes an associative array and creates a price field object.
   *
   * the function extract all the params it needs to initialize the create a
   * price field object. the params array could contain additional unused name/value
   * pairs
   *
   * @param array $params
   *   (reference) an assoc array of name/value pairs.
   *
   * @return CRM_Price_BAO_PriceField
   */
  public static function add(&$params) {
    $priceFieldBAO = new CRM_Price_BAO_PriceField();

    $priceFieldBAO->copyValues($params);

    if ($id = CRM_Utils_Array::value('id', $params)) {
      $priceFieldBAO->id = $id;
    }

    $priceFieldBAO->save();
    return $priceFieldBAO;
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
    $optionsIds = array();
    $maxIndex = CRM_Price_Form_Field::NUM_OPTION;
    if ($priceField->html_type == 'Text') {
      $maxIndex = 1;
      $fieldOptions = civicrm_api3('price_field_value', 'get', array(
        'price_field_id' => $priceField->id,
        'sequential' => 1,
      ));
      foreach ($fieldOptions['values'] as $option) {
        $optionsIds['id'] = $option['id'];
        // CRM-19741 If we are dealing with price fields that are Text only set the field value label to match
        if (!empty($params['id']) && $priceField->label != $option['label']) {
          $fieldValue = new CRM_Price_DAO_PriceFieldValue();
          $fieldValue->label = $priceField->label;
          $fieldValue->id = $option['id'];
          $fieldValue->save();
        }
      }
    }
    $defaultArray = array();
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
        $options = array(
          'price_field_id' => $priceField->id,
          'label' => trim($params['option_label'][$index]),
          'name' => CRM_Utils_String::munge($params['option_label'][$index], '_', 64),
          'amount' => CRM_Utils_Rule::cleanMoney(trim($params['option_amount'][$index])),
          'count' => CRM_Utils_Array::value($index, CRM_Utils_Array::value('option_count', $params), NULL),
          'max_value' => CRM_Utils_Array::value($index, CRM_Utils_Array::value('option_max_value', $params), NULL),
          'description' => CRM_Utils_Array::value($index, CRM_Utils_Array::value('option_description', $params), NULL),
          'membership_type_id' => CRM_Utils_Array::value($index, CRM_Utils_Array::value('membership_type_id', $params), NULL),
          'weight' => $params['option_weight'][$index],
          'is_active' => 1,
          'is_default' => CRM_Utils_Array::value($params['option_weight'][$index], $defaultArray) ? $defaultArray[$params['option_weight'][$index]] : 0,
          'membership_num_terms' => NULL,
          'non_deductible_amount' => CRM_Utils_Array::value('non_deductible_amount', $params),
          'visibility_id' => CRM_Utils_Array::value($index, CRM_Utils_Array::value('option_visibility_id', $params), self::getVisibilityOptionID('public')),
        );

        if ($options['membership_type_id']) {
          $options['membership_num_terms'] = CRM_Utils_Array::value($index, CRM_Utils_Array::value('membership_num_terms', $params), 1);
          $options['is_default'] = CRM_Utils_Array::value($params['membership_type_id'][$index], $defaultArray) ? $defaultArray[$params['membership_type_id'][$index]] : 0;
        }

        if (CRM_Utils_Array::value($index, CRM_Utils_Array::value('option_financial_type_id', $params))) {
          $options['financial_type_id'] = $params['option_financial_type_id'][$index];
        }
        elseif (!empty($params['financial_type_id'])) {
          $options['financial_type_id'] = $params['financial_type_id'];
        }
        if ($opIds = CRM_Utils_Array::value('option_id', $params)) {
          if ($opId = CRM_Utils_Array::value($index, $opIds)) {
            $optionsIds['id'] = $opId;
          }
          else {
            $optionsIds['id'] = NULL;
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
        $optionsLoad = civicrm_api3('price_field_value', 'get', array('id' => $optionsIds['id']));
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
    return $priceField;
  }

  /**
   * Fetch object based on array of properties.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Price_DAO_PriceField
   */
  public static function retrieve(&$params, &$defaults) {
    return CRM_Core_DAO::commonRetrieve('CRM_Price_DAO_PriceField', $params, $defaults);
  }

  /**
   * Update the is_active flag in the db.
   *
   * @param int $id
   *   Id of the database record.
   * @param bool $is_active
   *   Value we want to set the is_active field.
   *
   * @return Object
   *   DAO object on success, null otherwise.
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
    $freezeOptions = array()
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
      $is_pay_later = CRM_Utils_Array::value('is_pay_later', $qf->_values);
    }

    $otherAmount = $qf->get('values');
    $config = CRM_Core_Config::singleton();
    $currencySymbol = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_Currency', $config->defaultCurrency, 'symbol', 'name');
    $qf->assign('currencySymbol', $currencySymbol);
    // get currency name for price field and option attributes
    $currencyName = $config->defaultCurrency;

    if (!isset($label)) {
      $label = (!empty($qf->_membershipBlock) && $field->name == 'contribution_amount') ? ts('Additional Contribution') : $field->label;
    }

    if ($field->name == 'contribution_amount') {
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
    $seperator = '|';
    $invoiceSettings = Civi::settings()->get('contribution_invoice_settings');
    $taxTerm = CRM_Utils_Array::value('tax_term', $invoiceSettings);
    $displayOpt = CRM_Utils_Array::value('tax_display_settings', $invoiceSettings);
    $invoicing = CRM_Utils_Array::value('invoicing', $invoiceSettings);
    switch ($field->html_type) {
      case 'Text':
        $optionKey = key($customOption);
        $count = CRM_Utils_Array::value('count', $customOption[$optionKey], '');
        $max_value = CRM_Utils_Array::value('max_value', $customOption[$optionKey], '');
        $taxAmount = CRM_Utils_Array::value('tax_amount', $customOption[$optionKey]);
        if (isset($taxAmount) && $displayOpt && $invoicing) {
          $qf->assign('displayOpt', $displayOpt);
          $qf->assign('taxTerm', $taxTerm);
          $qf->assign('invoicing', $invoicing);
        }
        $priceVal = implode($seperator, array(
          $customOption[$optionKey][$valueFieldName] + $taxAmount,
          $count,
          $max_value,
        ));

        $extra = array();
        if (!empty($qf->_membershipBlock) && !empty($qf->_quickConfig) && $field->name == 'other_amount' && empty($qf->_contributionAmount)) {
          $useRequired = 0;
        }
        elseif (!empty($fieldOptions[$optionKey]['label'])) {
          //check for label.
          $label = $fieldOptions[$optionKey]['label'];
          if (!empty($qf->_quickConfig) && !empty($qf->_contributionAmount) && strtolower($fieldOptions[$optionKey]['name']) == 'other_amount') {
            $label .= '  ' . $currencySymbol;
            $qf->assign('priceset', $elementName);
            $extra = array('onclick' => 'useAmountOther();');
          }
        }

        $element = &$qf->add('text', $elementName, $label,
          array_merge($extra,
            array(
              'price' => json_encode(array($optionKey, $priceVal)),
              'size' => '4',
            )
          ),
          $useRequired && $field->is_required
        );
        if ($is_pay_later) {
          $qf->add('text', 'txt-' . $elementName, $label, array('size' => '4'));
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
          $message = ts('%1 must be a number (with or without decimal point).', array(1 => $label));
          $type = 'numeric';
        }
        // integers will have numeric rule applied to them.
        $qf->addRule($elementName, $message, $type);
        break;

      case 'Radio':
        $choice = array();

        if (!empty($qf->_quickConfig) && !empty($qf->_contributionAmount)) {
          $qf->assign('contriPriceset', $elementName);
        }

        foreach ($customOption as $opId => $opt) {
          $taxAmount = CRM_Utils_Array::value('tax_amount', $opt);
          if ($field->is_display_amounts) {
            $opt['label'] = !empty($opt['label']) ? $opt['label'] . '<span class="crm-price-amount-label-separator">&nbsp;-&nbsp;</span>' : '';
            $preHelpText = $postHelpText = '';
            if (!empty($opt['help_pre'])) {
              $preHelpText = '<span class="crm-price-amount-help-pre description">' . $opt['help_pre'] . '</span>: ';
            }
            if (!empty($opt['help_post'])) {
              $postHelpText = ': <span class="crm-price-amount-help-post description">' . $opt['help_post'] . '</span>';
            }
            if (isset($taxAmount) && $invoicing) {
              if ($displayOpt == 'Do_not_show') {
                $opt['label'] = '<span class="crm-price-amount-label">' . $opt['label'] . '</span>' . '<span class="crm-price-amount-amount">' . CRM_Utils_Money::format($opt[$valueFieldName] + $taxAmount) . '</span>';
              }
              elseif ($displayOpt == 'Inclusive') {
                $opt['label'] = '<span class="crm-price-amount-label">' . $opt['label'] . '</span>' . '<span class="crm-price-amount-amount">' . CRM_Utils_Money::format($opt[$valueFieldName] + $taxAmount) . '</span>';
                $opt['label'] .= '<span class="crm-price-amount-tax"> (includes ' . $taxTerm . ' of ' . CRM_Utils_Money::format($opt['tax_amount']) . ')</span>';
              }
              else {
                $opt['label'] = '<span class="crm-price-amount-label">' . $opt['label'] . '</span>' . '<span class="crm-price-amount-amount">' . CRM_Utils_Money::format($opt[$valueFieldName]) . '</span>';
                $opt['label'] .= '<span class="crm-price-amount-tax"> + ' . CRM_Utils_Money::format($opt['tax_amount']) . ' ' . $taxTerm . '</span>';
              }
            }
            else {
              $opt['label'] = '<span class="crm-price-amount-label">' . $opt['label'] . '</span>' . '<span class="crm-price-amount-amount">' . CRM_Utils_Money::format($opt[$valueFieldName]) . '</span>';
            }
            $opt['label'] = $preHelpText . $opt['label'] . $postHelpText;
          }
          $count = CRM_Utils_Array::value('count', $opt, '');
          $max_value = CRM_Utils_Array::value('max_value', $opt, '');
          $priceVal = implode($seperator, array($opt[$valueFieldName] + $taxAmount, $count, $max_value));
          if (isset($opt['visibility_id'])) {
            $visibility_id = $opt['visibility_id'];
          }
          else {
            $visibility_id = self::getVisibilityOptionID('public');
          }
          $extra = array(
            'price' => json_encode(array($elementName, $priceVal)),
            'data-amount' => $opt[$valueFieldName],
            'data-currency' => $currencyName,
            'data-price-field-values' => json_encode($customOption),
            'visibility' => $visibility_id,
          );
          if (!empty($qf->_quickConfig) && $field->name == 'contribution_amount') {
            $extra += array('onclick' => 'clearAmountOther();');
          }
          if ($field->name == 'membership_amount') {
            $extra += array(
              'onclick' => "return showHideAutoRenew({$opt['membership_type_id']});",
              'membership-type' => $opt['membership_type_id'],
            );
            $qf->assign('membershipFieldID', $field->id);
          }

          $choice[$opId] = $qf->createElement('radio', NULL, '', $opt['label'], $opt['id'], $extra);
          if ($is_pay_later) {
            $qf->add('text', 'txt-' . $elementName, $label, array('size' => '4'));
          }

          // CRM-6902 - Add "max" option for a price set field
          if (in_array($opId, $freezeOptions)) {
            self::freezeIfEnabled($choice[$opId], $customOption[$opId]);
            // CRM-14696 - Improve display for sold out price set options
            $choice[$opId]->setText('<span class="sold-out-option">' . $choice[$opId]->getText() . '&nbsp;(' . ts('Sold out') . ')</span>');
          }
        }
        if (!empty($qf->_membershipBlock) && $field->name == 'contribution_amount') {
          $choice[] = $qf->createElement('radio', NULL, '', ts('No thank you'), '-1',
            array(
              'price' => json_encode(array($elementName, '0|0')),
              'data-currency' => $currencyName,
              'onclick' => 'clearAmountOther();',
            )
          );
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

          $choice[] = $qf->createElement('radio', NULL, '', $none, '0',
            array('price' => json_encode(array($elementName, '0')))
          );
        }

        $element = &$qf->addGroup($choice, $elementName, $label);

        // make contribution field required for quick config when membership block is enabled
        if (($field->name == 'membership_amount' || $field->name == 'contribution_amount')
          && !empty($qf->_membershipBlock) && !$field->is_required
        ) {
          $useRequired = $field->is_required = TRUE;
        }

        if ($useRequired && $field->is_required) {
          $qf->addRule($elementName, ts('%1 is a required field.', array(1 => $label)), 'required');
        }
        break;

      case 'Select':
        $selectOption = $allowedOptions = $priceVal = array();

        foreach ($customOption as $opt) {
          $taxAmount = CRM_Utils_Array::value('tax_amount', $opt);
          $count = CRM_Utils_Array::value('count', $opt, '');
          $max_value = CRM_Utils_Array::value('max_value', $opt, '');

          if ($field->is_display_amounts) {
            $opt['label'] .= '&nbsp;-&nbsp;';
            if (isset($taxAmount) && $invoicing) {
              $opt['label'] = $opt['label'] . self::getTaxLabel($opt, $valueFieldName, $displayOpt, $taxTerm);
            }
            else {
              $opt['label'] = $opt['label'] . CRM_Utils_Money::format($opt[$valueFieldName]);
            }
          }

          $priceVal[$opt['id']] = implode($seperator, array($opt[$valueFieldName] + $taxAmount, $count, $max_value));

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
            $qf->add('text', 'txt-' . $elementName, $label, array('size' => '4'));
          }
        }
        if (isset($opt['visibility_id'])) {
          $visibility_id = $opt['visibility_id'];
        }
        else {
          $visibility_id = self::getVisibilityOptionID('public');
        }
        $element = &$qf->add('select', $elementName, $label,
          array(
            '' => ts('- select -'),
          ) + $selectOption,
          $useRequired && $field->is_required,
          array('price' => json_encode($priceVal), 'class' => 'crm-select2', 'data-price-field-values' => json_encode($customOption))
        );

        // CRM-6902 - Add "max" option for a price set field
        $button = substr($qf->controller->getButtonName(), -4);
        if (!empty($freezeOptions) && $button != 'skip') {
          $qf->addRule($elementName, ts('Sorry, this option is currently sold out.'), 'regex', "/" . implode('|', $allowedOptions) . "/");
        }
        break;

      case 'CheckBox':

        $check = array();
        foreach ($customOption as $opId => $opt) {
          $taxAmount = CRM_Utils_Array::value('tax_amount', $opt);
          $count = CRM_Utils_Array::value('count', $opt, '');
          $max_value = CRM_Utils_Array::value('max_value', $opt, '');

          if ($field->is_display_amounts) {
            $preHelpText = $postHelpText = '';
            if (isset($opt['help_pre'])) {
              $preHelpText = '<span class="crm-price-amount-help-pre description">' . $opt['help_pre'] . '</span>: ';
            }
            if (isset($opt['help_post'])) {
              $postHelpText = ': <span class="crm-price-amount-help-post description">' . $opt['help_post'] . '</span>';
            }
            $opt['label'] = '<span class="crm-price-amount-label">' . $opt['label'] . '</span>&nbsp;-&nbsp;';
            if (isset($taxAmount) && $invoicing) {
              $opt['label'] .= self::getTaxLabel($opt, $valueFieldName, $displayOpt, $taxTerm);
            }
            else {
              $opt['label'] .= CRM_Utils_Money::format($opt[$valueFieldName]);
            }
            $opt['label'] = $preHelpText . $opt['label'] . $postHelpText;
          }
          $priceVal = implode($seperator, array($opt[$valueFieldName] + $taxAmount, $count, $max_value));
          $check[$opId] = &$qf->createElement('checkbox', $opt['id'], NULL, $opt['label'],
            array(
              'price' => json_encode(array($opt['id'], $priceVal)),
              'data-amount' => $opt[$valueFieldName],
              'data-currency' => $currencyName,
              'visibility' => $opt['visibility_id'],
            )
          );
          if ($is_pay_later) {
            $txtcheck[$opId] =& $qf->createElement('text', $opId, $opt['label'], array('size' => '4'));
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
          $qf->addRule($elementName, ts('%1 is a required field.', array(1 => $label)), 'required');
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
      Civi::$statics[__CLASS__]['priceOptions'] = array();
      // This would happen if the function was only called to clear the cache.
      if (empty($fieldId)) {
        return array();
      }
    }

    if (empty(Civi::$statics[__CLASS__]['priceOptions'][$fieldId])) {
      $values = $options = array();
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

    $dao = CRM_Core_DAO::executeQuery($query, array(
      1 => array($optionGroupName, 'String'),
      2 => array($optionLabel, 'String'),
    ));

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
      $fieldValues = array('price_set_id' => $field->price_set_id);

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
      $htmlTypes = array(
        'Text' => ts('Text / Numeric Quantity'),
        'Select' => ts('Select'),
        'Radio' => ts('Radio'),
        'CheckBox' => ts('CheckBox'),
      );
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

    $priceFields = array();

    if ($allowNoneSelection) {
      $noneSelectedPriceFields = array();
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
      $htmlTypes = array();
      while ($fieldDAO->fetch()) {
        $htmlTypes[$fieldDAO->id] = $fieldDAO->html_type;
      }

      $selectedAmounts = array();

      foreach ($htmlTypes as $fieldId => $type) {
        $options = array();
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
        elseif (in_array($fields["price_{$fieldId}"], array_keys($options))) {
          $selectedAmounts[$fields["price_{$fieldId}"]] = $options[$fields["price_{$fieldId}"]]['amount'];
        }
      }

      list($componentName) = explode(':', $fields['_qf_default']);
      // now we have all selected amount in hand.
      $totalAmount = array_sum($selectedAmounts);
      // The form offers a field to enter the amount paid. This may differ from the amount that is due to complete the purchase
      $totalPaymentAmountEnteredOnForm = CRM_Utils_Array::value('partial_payment_total', $fields, CRM_Utils_Array::value('total_amount', $fields));
      if ($totalAmount < 0) {
        $error['_qf_default'] = ts('%1 amount can not be less than zero. Please select the options accordingly.', array(1 => $componentName));
      }
      elseif ($totalAmount > 0 &&
        $totalPaymentAmountEnteredOnForm >= $totalAmount && // if total amount is equal to all selected amount in hand
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
   * @param string $displayOpt
   *   Tax display setting option.
   *
   * @param string $taxTerm
   *
   * @return string
   *   Tax label for custom field.
   */
  public static function getTaxLabel($opt, $valueFieldName, $displayOpt, $taxTerm) {
    if ($displayOpt == 'Do_not_show') {
      $label = CRM_Utils_Money::format($opt[$valueFieldName] + $opt['tax_amount']);
    }
    elseif ($displayOpt == 'Inclusive') {
      $label = CRM_Utils_Money::format($opt[$valueFieldName] + $opt['tax_amount']);
      $label .= '<span class="crm-price-amount-tax"> (includes ' . $taxTerm . ' of ' . CRM_Utils_Money::format($opt['tax_amount']) . ')</span>';
    }
    else {
      $label = CRM_Utils_Money::format($opt[$valueFieldName]);
      $label .= '<span class="crm-price-amount-tax"> + ' . CRM_Utils_Money::format($opt['tax_amount']) . ' ' . $taxTerm . '</span>';
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
      self::$visibilityOptionsKeys = CRM_Price_BAO_PriceField::buildOptions(
        'visibility_id',
        NULL,
        array(
          'labelColumn' => 'name',
          'flip' => TRUE,
        )
      );
    }

    if (isset(self::$visibilityOptionsKeys[$visibilityName])) {
      return self::$visibilityOptionsKeys[$visibilityName];
    }

    return 0;
  }

}
