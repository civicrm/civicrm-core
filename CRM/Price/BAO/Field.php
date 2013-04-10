<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.3                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * Business objects for managing price fields.
 *
 */
class CRM_Price_BAO_Field extends CRM_Price_DAO_Field {

  protected $_options;

  /**
   * takes an associative array and creates a price field object
   *
   * the function extract all the params it needs to initialize the create a
   * price field object. the params array could contain additional unused name/value
   * pairs
   *
   * @param array  $params    (reference ) an assoc array of name/value pairs
   * @param array  $ids       the array that holds all the db ids
   *
   * @return object CRM_Price_BAO_Field object
   * @access public
   * @static
   */
  static function &add(&$params) {
    $priceFieldBAO = new CRM_Price_BAO_Field();

    $priceFieldBAO->copyValues($params);

    if ($id = CRM_Utils_Array::value('id', $params)) {
      $priceFieldBAO->id = $id;
    }

    $priceFieldBAO->save();
    return $priceFieldBAO;
  }

  /**
   * takes an associative array and creates a price field object
   *
   * This function is invoked from within the web form layer and also from the api layer
   *
   * @param array $params (reference) an assoc array of name/value pairs
   *
   * @return object CRM_Price_DAO_Field object
   * @access public
   * @static
   */
  static function create(&$params) {
    $transaction = new CRM_Core_Transaction();

    $priceField = self::add($params);

    if (is_a($priceField, 'CRM_Core_Error')) {
      $transaction->rollback();
      return $priceField;
    }

    $options = $optionsIds = array();
    $maxIndex = CRM_Price_Form_Field::NUM_OPTION;

    if ($priceField->html_type == 'Text') {
      $maxIndex = 1;

      $fieldValue = new CRM_Price_DAO_FieldValue();
      $fieldValue->price_field_id = $priceField->id;

      // update previous field values( if any )
      if ($fieldValue->find(TRUE)) {
        $optionsIds['id'] = $fieldValue->id;
      }
    }
    $defaultArray = array();
    if ($params['html_type'] == 'CheckBox' && isset($params['default_checkbox_option'])) {
      $tempArray = array_keys($params['default_checkbox_option']);
      foreach ($tempArray as $v) {
        if ($params['option_amount'][$v]) {
          $defaultArray[$v] = 1;
        }
      }
    }
    else {
      if (CRM_Utils_Array::value('default_option', $params)) {
        $defaultArray[$params['default_option']] = 1;
      }
    }

    for ($index = 1; $index <= $maxIndex; $index++) {
      if (array_key_exists('option_amount', $params) &&
        array_key_exists($index, $params['option_amount']) &&
        (CRM_Utils_Array::value($index, CRM_Utils_Array::value('option_label', $params)) ||
          CRM_Utils_Array::value('is_quick_config', $params)) &&
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
        );

        if ($options['membership_type_id']) {
          $options['membership_num_terms'] = CRM_Utils_Array::value($index, CRM_Utils_Array::value('membership_num_terms', $params), 1);
        }

        if (CRM_Utils_Array::value( $index, CRM_Utils_Array::value('option_financial_type_id', $params))) {
          $options['financial_type_id'] =  $params['option_financial_type_id'][$index];
        } elseif (CRM_Utils_Array::value( 'financial_type_id', $params )) {
          $options['financial_type_id'] = $params['financial_type_id'];
        }

        if ($opIds = CRM_Utils_Array::value('option_id', $params)) {
          if ($opId = CRM_Utils_Array::value($index, $opIds)) {
            $optionsIds['id'] = $opId;
          } else {
            $optionsIds['id'] = NULL;
          }
        }
        CRM_Price_BAO_FieldValue::create($options, $optionsIds);
      }
    }

    $transaction->commit();
    return $priceField;
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Price_DAO_Field object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    return CRM_Core_DAO::commonRetrieve('CRM_Price_DAO_Field', $params, $defaults);
  }

  /**
   * update the is_active flag in the db
   *
   * @param int      $id         Id of the database record
   * @param boolean  $is_active  Value we want to set the is_active field
   *
   * @return   Object            DAO object on sucess, null otherwise
   *
   * @access public
   * @static
   */
  static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Price_DAO_Field', $id, 'is_active', $is_active);
  }

  /**
   * Get the field title.
   *
   * @param int $id id of field.
   *
   * @return string name
   *
   * @access public
   * @static
   *
   */
  public static function getTitle($id) {
    return CRM_Core_DAO::getFieldValue('CRM_Price_DAO_Field', $id, 'label');
  }

  /**
   * This function for building custom fields
   *
   * @param object  $qf             form object (reference)
   * @param string  $elementName    name of the custom field
   * @param boolean $inactiveNeeded
   * @param boolean $useRequired    true if required else false
   * @param boolean $search         true if used for search else false
   * @param string  $label          label for custom field
   *
   * @access public
   * @static
   */
  public static function addQuickFormElement(&$qf,
    $elementName,
    $fieldId,
    $inactiveNeeded,
    $useRequired = TRUE,
    $label = NULL,
    $fieldOptions = NULL,
    $feezeOptions = array()
  ) {

    $field = new CRM_Price_DAO_Field();
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
      $is_pay_later = CRM_Utils_Array::value( 'is_pay_later', $qf->_values);
    }

    $otherAmount = $qf->get('values');
    $config = CRM_Core_Config::singleton();
    $currencySymbol = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_Currency',$config->defaultCurrency,'symbol','name');
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
      $customOption = CRM_Price_BAO_Field::getOptions($field->id, $inactiveNeeded);
    }

    //use value field.
    $valueFieldName = 'amount';
    $seperator = '|';
    switch ($field->html_type) {
      case 'Text':
        $optionKey = key($customOption);
        $count = CRM_Utils_Array::value('count', $customOption[$optionKey], '');
        $max_value = CRM_Utils_Array::value('max_value', $customOption[$optionKey], '');
        $priceVal = implode($seperator, array($customOption[$optionKey][$valueFieldName], $count, $max_value));

        $extra = array();
        if (!empty($qf->_quickConfig) && !empty($qf->_contributionAmount)) {
          foreach($fieldOptions as &$fieldOption) {
            if ($fieldOption['name'] == 'other_amount') {
              $fieldOption['label'] = $fieldOption['label'] . '  ' . $currencySymbol;
            }
          }
          $qf->assign('priceset', $elementName);
          $extra = array('onclick' => 'useAmountOther();');
        }

        // if seperate membership payment is used with quick config priceset then change the other amount label
        if (!empty($qf->_membershipBlock) && !empty($qf->_quickConfig) && $field->name == 'other_amount' && empty($qf->_contributionAmount)) {
          $label = ts('Additional Contribution');
          $useRequired = 0;
        } 
        elseif (CRM_Utils_Array::value('label', $fieldOptions[$optionKey])) {      //check for label.
          $label = $fieldOptions[$optionKey]['label'];
        }

        $element = &$qf->add('text', $elementName, $label,
                   array_merge($extra,
                     array('price' => json_encode(array($optionKey, $priceVal)),
                       'size' => '4',
                     )
                   ),
                   $useRequired && $field->is_required
        );
        if ($is_pay_later) {
          $qf->add( 'text', 'txt-'.$elementName, $label, array( 'size' => '4'));
        }

        // CRM-6902
        if (in_array($optionKey, $feezeOptions)) {
          $element->freeze();
        }

        //CRM-10117
        if (!empty($qf->_quickConfig)) {
          $message = ts('Please enter a valid amount.');
          $type = 'money';
        } 
        else {
          $message = ts('%1 must be an integer (whole number).', array(1 => $label));
          $type = 'positiveInteger';
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
          if ($field->is_display_amounts) {
            $opt['label'] =  CRM_Utils_Array::value('label', $opt) ? $opt['label'] : '';
            $opt['label'] = '<span class="crm-price-amount-amount">' . CRM_Utils_Money::format($opt[$valueFieldName]) . '</span> <span class="crm-price-amount-label">' . $opt['label'] . '</span>';
          }
          $count = CRM_Utils_Array::value('count', $opt, '');
          $max_value = CRM_Utils_Array::value('max_value', $opt, '');
          $priceVal = implode($seperator, array($opt[$valueFieldName], $count, $max_value));
          $extra = array('price' => json_encode(array($elementName, $priceVal)),
                   'data-amount' => $opt[$valueFieldName],
                   'data-currency' => $currencyName,
          );
          if (!empty($qf->_quickConfig) && $field->name == 'contribution_amount') {
            $extra += array('onclick' => 'clearAmountOther();');
          } 
          elseif (!empty($qf->_quickConfig) && $field->name == 'membership_amount') {
            $extra += array(
              'onclick' => "return showHideAutoRenew({$opt['membership_type_id']});",
              'membership-type' => $opt['membership_type_id'],
            ); 
            $qf->assign('membershipFieldID',$field->id);
          }
            
          $choice[$opId] = $qf->createElement('radio', NULL, '', $opt['label'], $opt['id'], $extra);

          if ($is_pay_later) {
            $qf->add( 'text', 'txt-'.$elementName, $label, array( 'size' => '4'));
          }

          // CRM-6902
          if (in_array($opId, $feezeOptions)) {
            $choice[$opId]->freeze();
          }
        }
        if (!empty($qf->_membershipBlock) && $field->name == 'contribution_amount') {
          $choice[] = $qf->createElement('radio', NULL, '', ts('No thank you'), '-1',
            array(
              'onclick' => 'clearAmountOther();',
            )
          );
        }

        if (!$field->is_required) {
          // add "none" option
          if (CRM_Utils_Array::value('is_allow_other_amount', $otherAmount) && $field->name == 'contribution_amount') {
            $none = ts('Other Amount');
          } 
          elseif (!empty($qf->_membershipBlock) && !CRM_Utils_Array::value('is_required', $qf->_membershipBlock) && $field->name == 'membership_amount') {
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
          && !empty($qf->_membershipBlock) && !$field->is_required) {
          $useRequired = $field->is_required = TRUE;
        }

        if ($useRequired && $field->is_required) {
          $qf->addRule($elementName, ts('%1 is a required field.', array(1 => $label)), 'required');
        }
        break;

      case 'Select':
        $selectOption = $allowedOptions = $priceVal = array();

        foreach ($customOption as $opt) {
          $count = CRM_Utils_Array::value('count', $opt, '');
          $max_value = CRM_Utils_Array::value('max_value', $opt, '');
          $priceVal[$opt['id']] = implode($seperator, array($opt[$valueFieldName], $count, $max_value));

          if ($field->is_display_amounts) {
            $opt['label'] .= '&nbsp;-&nbsp;';
            $opt['label'] .= CRM_Utils_Money::format($opt[$valueFieldName]);
          }
          $selectOption[$opt['id']] = $opt['label'];

          if (!in_array($opt['id'], $feezeOptions)) {
            $allowedOptions[] = $opt['id'];
          }
          if ($is_pay_later) {
            $qf->add( 'text', 'txt-'.$elementName, $label, array( 'size' => '4'));
          }
        }
        $element = &$qf->add('select', $elementName, $label,
          array(
            '' => ts('- select -')) + $selectOption,
            $useRequired && $field->is_required,
            array('price' => json_encode($priceVal))
          );

        // CRM-6902
        $button = substr($qf->controller->getButtonName(), -4);
        if (!empty($feezeOptions) && $button != 'skip') {
          $qf->addRule($elementName, ts('Sorry, this option is currently sold out.'), 'regex', "/" . implode('|', $allowedOptions) . "/");
        }
        break;

      case 'CheckBox':

        $check = array();
        foreach ($customOption as $opId => $opt) {
          $count = CRM_Utils_Array::value('count', $opt, '');
          $max_value = CRM_Utils_Array::value('max_value', $opt, '');
          $priceVal = implode($seperator, array($opt[$valueFieldName], $count, $max_value));

          if ($field->is_display_amounts) {
            $opt['label'] .= '&nbsp;-&nbsp;';
            $opt['label'] .= CRM_Utils_Money::format($opt[$valueFieldName]);
          }
          $check[$opId] = &$qf->createElement('checkbox', $opt['id'], NULL, $opt['label'],
            array('price' => json_encode(array($opt['id'], $priceVal)),
             'data-amount' => $opt[$valueFieldName],
             'data-currency' => $currencyName,
            )
          );
          if ($is_pay_later) {
            $txtcheck[$opId] =& $qf->createElement( 'text', $opId, $opt['label'], array( 'size' => '4' ) );
            $qf->addGroup($txtcheck, 'txt-'.$elementName, $label);
          }
          // CRM-6902
          if (in_array($opId, $feezeOptions)) {
            $check[$opId]->freeze();
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
   * Retrieve a list of options for the specified field
   *
   * @param int $fieldId price field ID
   * @param bool $inactiveNeeded include inactive options
   * @param bool $reset ignore stored values\
   *
   * @return array array of options
   */
  public static function getOptions($fieldId, $inactiveNeeded = FALSE, $reset = FALSE) {
    static $options = array();

    if ($reset || empty($options[$fieldId])) {
      $values = array();
      CRM_Price_BAO_FieldValue::getValues($fieldId, $values, 'weight', !$inactiveNeeded);
      $options[$fieldId] = $values;
    }

    return $options[$fieldId];
  }

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

    $dao = CRM_Core_DAO::executeQuery($query, array(1 => array($optionGroupName, 'String'), 2 => array($optionLabel, 'String')));

    while ($dao->fetch()) {
      return $dao->id;
    }
  }

  /**
   * Delete the price set field.
   *
   * @param   int   $id    Field Id
   *
   * @return  boolean
   *
   * @access public
   * @static
   *
   */
  public static function deleteField($id) {
    $field = new CRM_Price_DAO_Field();
    $field->id = $id;

    if ($field->find(TRUE)) {
      // delete the options for this field
      CRM_Price_BAO_FieldValue::deleteValues($id);

      // reorder the weight before delete
      $fieldValues = array('price_set_id' => $field->price_set_id);

      CRM_Utils_Weight::delWeight('CRM_Price_DAO_Field', $field->id, $fieldValues);

      // now delete the field
      return $field->delete();
    }

    return NULL;
  }

  static function &htmlTypes() {
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
   * Validate the priceset
   *
   * @param int $priceSetId, array $fields
   *
   * retrun the error string
   *
   * @access public
   * @static
   *
   */

  public static function priceSetValidation($priceSetId, $fields, &$error, $allowNoneSelection = FALSE) {
    // check for at least one positive
    // amount price field should be selected.
    $priceField = new CRM_Price_DAO_Field();
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

      if (CRM_Utils_Array::value($key, $fields)) {
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
        CRM_Price_BAO_FieldValue::getValues($fieldId, $options);

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
      if ($totalAmount < 0) {
        $error['_qf_default'] = ts('%1 amount can not be less than zero. Please select the options accordingly.', array(1 => $componentName));
      }
    }
    else {
      if ($allowNoneSelection) {
        if (empty($noneSelectedPriceFields)) {
          $error['_qf_default'] = ts('Please select at least one option from price set.');
        }
      } else {
        $error['_qf_default'] = ts('Please select at least one option from price set.');
      }
    }
  }
}

