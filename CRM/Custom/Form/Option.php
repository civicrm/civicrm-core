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
 * form to process actions on the field aspect of Custom
 */
class CRM_Custom_Form_Option extends CRM_Core_Form {

  /**
   * The custom field id saved to the session for an update
   *
   * @var int
   */
  protected $_fid;

  /**
   * The custom group id saved to the session for an update
   *
   * @var int
   */
  protected $_gid;

  /**
   * The option group ID
   * @var int
   */
  protected $_optionGroupID = NULL;

  /**
   * The Option id, used when editing the Option
   *
   * @var int
   */
  protected $_id;

  /**
   * Set variables up before form is built.
   *
   * @return void
   */
  public function preProcess() {
    $this->_fid = CRM_Utils_Request::retrieve('fid', 'Positive', $this);

    $this->_gid = CRM_Utils_Request::retrieve('gid', 'Positive', $this);

    if (!isset($this->_gid) && $this->_fid) {
      $this->_gid = CRM_Core_DAO::getFieldValue(
        'CRM_Core_DAO_CustomField',
        $this->_fid,
        'custom_group_id'
      );
    }

    if ($this->_fid) {
      $this->_optionGroupID = CRM_Core_DAO::getFieldValue(
        'CRM_Core_DAO_CustomField',
        $this->_fid,
        'option_group_id'
      );
    }

    if ($isReserved = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $this->_gid, 'is_reserved', 'id')) {
      CRM_Core_Error::statusBounce("You cannot add or edit muliple choice options in a reserved custom field-set.");
    }

    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
  }

  /**
   * Set default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @return array
   *   array of default values
   */
  public function setDefaultValues() {
    $defaults = $fieldDefaults = [];
    if (isset($this->_id)) {
      $params = ['id' => $this->_id];
      CRM_Core_BAO_CustomOption::retrieve($params, $defaults);

      $paramsField = ['id' => $this->_fid];
      CRM_Core_BAO_CustomField::retrieve($paramsField, $fieldDefaults);
      if (CRM_Core_Bao_CustomField::isSerialized($fieldDefaults)) {
        if (!empty($fieldDefaults['default_value'])) {
          $defaultCheckValues = CRM_Utils_Array::explodePadded($fieldDefaults['default_value']);
          if (in_array($defaults['value'], $defaultCheckValues)) {
            $defaults['default_value'] = 1;
          }
        }
      }
      else {
        if (($fieldDefaults['default_value'] ?? NULL) == ($defaults['value'] ?? NULL)) {
          $defaults['default_value'] = 1;
        }
      }
    }
    else {
      $defaults['is_active'] = 1;
    }

    if ($this->_action & CRM_Core_Action::ADD) {
      $fieldValues = ['option_group_id' => $this->_optionGroupID];
      $defaults['weight'] = CRM_Utils_Weight::getDefaultWeight('CRM_Core_DAO_OptionValue', $fieldValues);
    }

    return $defaults;
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    if ($this->_action == CRM_Core_Action::DELETE) {
      $option = civicrm_api3('option_value', 'getsingle', ['id' => $this->_id]);
      $this->assign('label', $option['label']);
      $this->addButtons([
        [
          'type' => 'next',
          'name' => ts('Delete'),
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]);
    }
    else {
      // lets trim all the whitespace
      $this->applyFilter('__ALL__', 'trim');

      // hidden Option Id for validation use
      $this->add('hidden', 'optionId', $this->_id);

      //hidden field ID for validation use
      $this->add('hidden', 'fieldId', $this->_fid);

      // label
      $this->add('text', 'label', ts('Option Label'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'label'), TRUE);

      $this->add('text', 'value', ts('Option Value'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'value'), TRUE);

      $this->add('textarea', 'description', ts('Description'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'description'));
      // weight
      $this->add('number', 'weight', ts('Order'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'weight'), TRUE);
      $this->addRule('weight', ts('is a numeric field'), 'numeric');

      // is active ?
      $this->add('checkbox', 'is_active', ts('Active?'));

      // Set the default value for Custom Field
      $this->add('checkbox', 'default_value', ts('Default'));

      // add a custom form rule
      $this->addFormRule(['CRM_Custom_Form_Option', 'formRule'], $this);

      // add buttons
      $this->addButtons([
        [
          'type' => 'next',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ],
        [
          'type' => 'next',
          'name' => ts('Save and New'),
          'subName' => 'new',
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]);

      // if view mode pls freeze it with the done button.
      if ($this->_action & CRM_Core_Action::VIEW) {
        $this->freeze();
        $url = CRM_Utils_System::url('civicrm/admin/custom/group/field/option',
          'reset=1&action=browse&fid=' . $this->_fid . '&gid=' . $this->_gid,
          TRUE, NULL, FALSE
        );
        $this->addElement('xbutton',
          'done',
          CRM_Core_Page::crmIcon('fa-times') . ' ' . ts('Done'),
          [
            'type' => 'button',
            'onclick' => "location.href='$url'",
            'class' => 'crm-form-submit cancel',
          ]
        );
      }
    }
    $this->assign('id', $this->_id);
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   *
   * @param $files
   * @param CRM_Core_Form $form
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($fields, $files, $form) {
    $optionLabel = $fields['label'];
    $optionValue = $fields['value'];
    $fieldId = $form->_fid;
    $optionGroupId = $form->_optionGroupID;

    $temp = [];
    if (empty($form->_id)) {
      $query = "
SELECT count(*)
  FROM civicrm_option_value
 WHERE option_group_id = %1
   AND label = %2";
      $params = [
        1 => [$optionGroupId, 'Integer'],
        2 => [$optionLabel, 'String'],
      ];
      if (CRM_Core_DAO::singleValueQuery($query, $params) > 0) {
        $errors['label'] = ts('There is an entry with the same label.');
      }

      $query = "
SELECT count(*)
  FROM civicrm_option_value
 WHERE option_group_id = %1
   AND value = %2";
      $params = [
        1 => [$optionGroupId, 'Integer'],
        2 => [$optionValue, 'String'],
      ];
      if (CRM_Core_DAO::singleValueQuery($query, $params) > 0) {
        $errors['value'] = ts('There is an entry with the same value.');
      }
    }
    else {
      //capture duplicate entries while updating Custom Options
      $optionId = CRM_Utils_Type::escape($fields['optionId'], 'Integer');

      //check label duplicates within a custom field
      $query = "
SELECT count(*)
  FROM civicrm_option_value
 WHERE option_group_id = %1
   AND id != %2
   AND label = %3";
      $params = [
        1 => [$optionGroupId, 'Integer'],
        2 => [$optionId, 'Integer'],
        3 => [$optionLabel, 'String'],
      ];
      if (CRM_Core_DAO::singleValueQuery($query, $params) > 0) {
        $errors['label'] = ts('There is an entry with the same label.');
      }

      //check value duplicates within a custom field
      $query = "
SELECT count(*)
  FROM civicrm_option_value
 WHERE option_group_id = %1
   AND id != %2
   AND value = %3";
      $params = [
        1 => [$optionGroupId, 'Integer'],
        2 => [$optionId, 'Integer'],
        3 => [$optionValue, 'String'],
      ];
      if (CRM_Core_DAO::singleValueQuery($query, $params) > 0) {
        $errors['value'] = ts('There is an entry with the same value.');
      }
    }

    $query = "
SELECT data_type
  FROM civicrm_custom_field
 WHERE id = %1";
    $params = [1 => [$fieldId, 'Integer']];
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    if ($dao->fetch()) {
      switch ($dao->data_type) {
        case 'Int':
          if (!CRM_Utils_Rule::integer($fields["value"])) {
            $errors['value'] = ts('Please enter a valid integer value.');
          }
          break;

        case 'Float':
          //     case 'Money':
          if (!CRM_Utils_Rule::numeric($fields["value"])) {
            $errors['value'] = ts('Please enter a valid number.');
          }
          break;

        case 'Money':
          if (!CRM_Utils_Rule::money($fields["value"])) {
            $errors['value'] = ts('Please enter a valid value.');
          }
          break;

        case 'Date':
          if (!CRM_Utils_Rule::date($fields["value"])) {
            $errors['value'] = ts('Please enter a valid date using YYYY-MM-DD format. Example: 2004-12-31.');
          }
          break;

        case 'Boolean':
          if (!CRM_Utils_Rule::integer($fields["value"]) &&
            ($fields["value"] != '1' || $fields["value"] != '0')
          ) {
            $errors['value'] = ts('Please enter 1 or 0 as value.');
          }
          break;

        case 'Country':
          if (!empty($fields["value"])) {
            $params = [1 => [$fields['value'], 'String']];
            $query = "SELECT count(*) FROM civicrm_country WHERE name = %1 OR iso_code = %1";
            if (CRM_Core_DAO::singleValueQuery($query, $params) <= 0) {
              $errors['value'] = ts('Invalid default value for country.');
            }
          }
          break;

        case 'StateProvince':
          if (!empty($fields["value"])) {
            $params = [1 => [$fields['value'], 'String']];
            $query = "
SELECT count(*)
  FROM civicrm_state_province
 WHERE name = %1
    OR abbreviation = %1";
            if (CRM_Core_DAO::singleValueQuery($query, $params) <= 0) {
              $errors['value'] = ts('The invalid value for State/Province data type');
            }
          }
          break;
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form.
   *
   * @return void
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->controller->exportValues('Option');

    if ($this->_action == CRM_Core_Action::DELETE) {
      $option = civicrm_api3('option_value', 'getsingle', ['id' => $this->_id]);
      $fieldValues = ['option_group_id' => $this->_optionGroupID];
      CRM_Utils_Weight::delWeight('CRM_Core_DAO_OptionValue', $this->_id, $fieldValues);
      CRM_Core_BAO_CustomOption::del($this->_id);
      CRM_Core_Session::setStatus(ts('Option "%1" has been deleted.', [1 => $option['label']]), ts('Deleted'), 'success');
      return;
    }

    // set values for custom field properties and save
    $customOption = new CRM_Core_DAO_OptionValue();
    $customOption->label = $params['label'];
    $customOption->weight = $params['weight'];
    $customOption->description = $params['description'];
    $customOption->value = $params['value'];
    $customOption->is_active = $params['is_active'] ?? FALSE;

    $oldWeight = NULL;
    if ($this->_id) {
      $customOption->id = $this->_id;
      $oldWeight = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $this->_id, 'weight', 'id');
    }
    else {
      $customOption->name = CRM_Utils_String::titleToVar($params['label']);
    }

    $fieldValues = ['option_group_id' => $this->_optionGroupID];
    $customOption->weight
      = CRM_Utils_Weight::updateOtherWeights(
        'CRM_Core_DAO_OptionValue',
        $oldWeight,
        $params['weight'],
        $fieldValues);

    $customOption->option_group_id = $this->_optionGroupID;

    $customField = new CRM_Core_DAO_CustomField();
    $customField->id = $this->_fid;
    if (
      $customField->find(TRUE) && CRM_Core_BAO_CustomField::isSerialized($customField)
    ) {
      $defVal = (array) CRM_Utils_Array::explodePadded($customField->default_value);
      if (!empty($params['default_value'])) {
        if (!in_array($customOption->value, $defVal)) {
          $defVal[] = $customOption->value;
          $newDefaultValue = CRM_Core_DAO::serializeField($defVal, CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND);
        }
      }
      elseif (in_array($customOption->value, $defVal)) {
        $tempVal = [];
        foreach ($defVal as $v) {
          if ($v != $customOption->value) {
            $tempVal[] = $v;
          }
        }

        $newDefaultValue = CRM_Core_DAO::serializeField($tempVal, CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND);
      }
    }
    else {
      switch ($customField->data_type) {
        case 'Money':
          $customOption->value = CRM_Utils_Rule::cleanMoney($customOption->value);
          break;

        case 'Int':
          $customOption->value = intval($customOption->value);
          break;

        case 'Float':
          $customOption->value = floatval($customOption->value);
          break;
      }

      if (!empty($params['default_value'])) {
        $newDefaultValue = $customOption->value;
      }
      elseif ($customField->find(TRUE) && $customField->default_value == $customOption->value) {
        // this is the case where this option is the current default value and we have been reset
        $newDefaultValue = '';
      }
    }

    if (isset($newDefaultValue)) {
      CRM_Core_BAO_CustomField::create(['id' => $this->_fid, 'default_value' => $newDefaultValue]);
    }

    if ($this->_id) {
      CRM_Core_BAO_CustomOption::updateValue($customOption->id, $customOption->value);
    }
    $customOption->save();

    $msg = ts('Your multiple choice option \'%1\' has been saved', [1 => $customOption->label]);
    CRM_Core_Session::setStatus($msg, '', 'success');
    $buttonName = $this->controller->getButtonName();
    $session = CRM_Core_Session::singleton();
    if ($buttonName == $this->getButtonName('next', 'new')) {
      CRM_Core_Session::setStatus(ts('You can add another option.'), '', 'info');
      $session->replaceUserContext(
        CRM_Utils_System::url(
          'civicrm/admin/custom/group/field/option',
          'reset=1&action=add&fid=' . $this->_fid . '&gid=' . $this->_gid
        )
      );
    }
  }

}
