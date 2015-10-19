<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */

/**
 * form to process actions on the field aspect of Price
 */
class CRM_Price_Form_Field extends CRM_Core_Form {

  /**
   * Constants for number of options for data types of multiple option.
   */
  const NUM_OPTION = 15;

  /**
   * The custom set id saved to the session for an update.
   *
   * @var int
   */
  protected $_sid;

  /**
   * The field id, used when editing the field
   *
   * @var int
   */
  protected $_fid;

  /**
   * The extended component Id.
   *
   * @var array
   */
  protected $_extendComponentId;

  /**
   * Variable is set if price set is used for membership.
   */
  protected $_useForMember;

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {

    $this->_sid = CRM_Utils_Request::retrieve('sid', 'Positive', $this, FALSE, NULL, 'REQUEST');
    $this->_fid = CRM_Utils_Request::retrieve('fid', 'Positive', $this, FALSE, NULL, 'REQUEST');
    $url = CRM_Utils_System::url('civicrm/admin/price/field', "reset=1&action=browse&sid={$this->_sid}");
    $breadCrumb = array(array('title' => ts('Price Set Fields'), 'url' => $url));

    $this->_extendComponentId = array();
    $extendComponentId = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $this->_sid, 'extends', 'id');
    if ($extendComponentId) {
      $this->_extendComponentId = explode(CRM_Core_DAO::VALUE_SEPARATOR, $extendComponentId);
    }

    CRM_Utils_System::appendBreadCrumb($breadCrumb);

    $this->setPageTitle(ts('Price Field'));
  }

  /**
   * Set default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @return array
   *   array of default values
   */
  public function setDefaultValues() {
    $defaults = array();
    // is it an edit operation ?
    if (isset($this->_fid)) {
      $params = array('id' => $this->_fid);
      $this->assign('fid', $this->_fid);
      CRM_Price_BAO_PriceField::retrieve($params, $defaults);
      $this->_sid = $defaults['price_set_id'];

      // if text, retrieve price
      if ($defaults['html_type'] == 'Text') {
        $isActive = $defaults['is_active'];
        $valueParams = array('price_field_id' => $this->_fid);

        CRM_Price_BAO_PriceFieldValue::retrieve($valueParams, $defaults);

        // fix the display of the monetary value, CRM-4038
        $defaults['price'] = CRM_Utils_Money::format($defaults['amount'], NULL, '%a');
        $defaults['is_active'] = $isActive;
      }

      if (!empty($defaults['active_on'])) {
        list($defaults['active_on'],
          $defaults['active_on_time']
          ) = CRM_Utils_Date::setDateDefaults($defaults['active_on'], 'activityDateTime');
      }

      if (!empty($defaults['expire_on'])) {
        list($defaults['expire_on'],
          $defaults['expire_on_time']
          ) = CRM_Utils_Date::setDateDefaults($defaults['expire_on'], 'activityDateTime');
      }
    }
    else {
      $defaults['is_active'] = 1;
      for ($i = 1; $i <= self::NUM_OPTION; $i++) {
        $defaults['option_status[' . $i . ']'] = 1;
        $defaults['option_weight[' . $i . ']'] = $i;
      }
    }

    if ($this->_action & CRM_Core_Action::ADD) {
      $fieldValues = array('price_set_id' => $this->_sid);
      $defaults['weight'] = CRM_Utils_Weight::getDefaultWeight('CRM_Price_DAO_PriceField', $fieldValues);
      $defaults['options_per_line'] = 1;
      $defaults['is_display_amounts'] = 1;
    }
    $enabledComponents = CRM_Core_Component::getEnabledComponents();
    $eventComponentId = NULL;
    if (array_key_exists('CiviEvent', $enabledComponents)) {
      $eventComponentId = CRM_Core_Component::getComponentID('CiviEvent');
    }

    if (isset($this->_sid) && $this->_action == CRM_Core_Action::ADD) {
      $financialTypeId = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $this->_sid, 'financial_type_id');
      $defaults['financial_type_id'] = $financialTypeId;
      for ($i = 1; $i <= self::NUM_OPTION; $i++) {
        $defaults['option_financial_type_id[' . $i . ']'] = $financialTypeId;
      }
    }
    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    // lets trim all the whitespace
    $this->applyFilter('__ALL__', 'trim');

    // add a hidden field to remember the price set id
    // this get around the browser tab issue
    $this->add('hidden', 'sid', $this->_sid);
    $this->add('hidden', 'fid', $this->_fid);

    // label
    $this->add('text', 'label', ts('Field Label'), CRM_Core_DAO::getAttribute('CRM_Price_DAO_PriceField', 'label'), TRUE);

    // html_type
    $javascript = 'onchange="option_html_type(this.form)";';

    $htmlTypes = CRM_Price_BAO_PriceField::htmlTypes();

    // Text box for Participant Count for a field

    // Financial Type
    $financialType = CRM_Financial_BAO_FinancialType::getIncomeFinancialType();
    foreach ($financialType as $finTypeId => $type) {
      if (CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus()
        && !CRM_Core_Permission::check('add contributions of type ' . $type)
      ) {
        unset($financialType[$finTypeId]);
      }
    }
    if (count($financialType)) {
      $this->assign('financialType', $financialType);
    }
    $enabledComponents = CRM_Core_Component::getEnabledComponents();
    $eventComponentId = $memberComponentId = NULL;
    if (array_key_exists('CiviEvent', $enabledComponents)) {
      $eventComponentId = CRM_Core_Component::getComponentID('CiviEvent');
    }
    if (array_key_exists('CiviMember', $enabledComponents)) {
      $memberComponentId = CRM_Core_Component::getComponentID('CiviMember');
    }

    $attributes = CRM_Core_DAO::getAttribute('CRM_Price_DAO_PriceFieldValue');

    $this->add('select', 'financial_type_id',
      ts('Financial Type'),
      array(' ' => ts('- select -')) + $financialType
    );

    $this->assign('useForMember', FALSE);
    if (in_array($eventComponentId, $this->_extendComponentId)) {
      $this->add('text', 'count', ts('Participant Count'), $attributes['count']);

      $this->addRule('count', ts('Participant Count should be a positive number'), 'positiveInteger');

      $this->add('text', 'max_value', ts('Max Participants'), $attributes['max_value']);
      $this->addRule('max_value', ts('Please enter a valid Max Participants.'), 'positiveInteger');

      $this->assign('useForEvent', TRUE);
    }
    else {
      if (in_array($memberComponentId, $this->_extendComponentId)) {
        $this->_useForMember = 1;
        $this->assign('useForMember', $this->_useForMember);
      }
      $this->assign('useForEvent', FALSE);
    }

    $sel = $this->add('select', 'html_type', ts('Input Field Type'),
      $htmlTypes, TRUE, $javascript
    );

    // price (for text inputs)
    $this->add('text', 'price', ts('Price'));
    $this->registerRule('price', 'callback', 'money', 'CRM_Utils_Rule');
    $this->addRule('price', ts('must be a monetary value'), 'money');

    if ($this->_action == CRM_Core_Action::UPDATE) {
      $this->freeze('html_type');
    }

    // form fields of Custom Option rows
    $_showHide = new CRM_Core_ShowHideBlocks('', '');

    for ($i = 1; $i <= self::NUM_OPTION; $i++) {

      //the show hide blocks
      $showBlocks = 'optionField_' . $i;
      if ($i > 2) {
        $_showHide->addHide($showBlocks);
        if ($i == self::NUM_OPTION) {
          $_showHide->addHide('additionalOption');
        }
      }
      else {
        $_showHide->addShow($showBlocks);
      }
      // label
      $attributes['label']['size'] = 25;
      $this->add('text', 'option_label[' . $i . ']', ts('Label'), $attributes['label']);

      // amount
      $this->add('text', 'option_amount[' . $i . ']', ts('Amount'), $attributes['amount']);
      $this->addRule('option_amount[' . $i . ']', ts('Please enter a valid amount for this field.'), 'money');

      //Financial Type
      $this->add(
        'select',
        'option_financial_type_id[' . $i . ']',
        ts('Financial Type'),
        array('' => ts('- select -')) + $financialType
      );
      if (in_array($eventComponentId, $this->_extendComponentId)) {
        // count
        $this->add('text', 'option_count[' . $i . ']', ts('Participant Count'), $attributes['count']);
        $this->addRule('option_count[' . $i . ']', ts('Please enter a valid Participants Count.'), 'positiveInteger');

        // max_value
        $this->add('text', 'option_max_value[' . $i . ']', ts('Max Participants'), $attributes['max_value']);
        $this->addRule('option_max_value[' . $i . ']', ts('Please enter a valid Max Participants.'), 'positiveInteger');

        // description
        //$this->add('textArea', 'option_description['.$i.']', ts('Description'), array('rows' => 1, 'cols' => 40 ));
      }
      elseif (in_array($memberComponentId, $this->_extendComponentId)) {
        $membershipTypes = CRM_Member_PseudoConstant::membershipType();
        $js = array('onchange' => "calculateRowValues( $i );");

        $this->add('select', 'membership_type_id[' . $i . ']', ts('Membership Type'),
          array('' => ' ') + $membershipTypes, FALSE, $js
        );
        $this->add('text', 'membership_num_terms[' . $i . ']', ts('Number of Terms'), CRM_Utils_Array::value('membership_num_terms', $attributes));
      }

      // weight
      $this->add('text', 'option_weight[' . $i . ']', ts('Order'), $attributes['weight']);

      // is active ?
      $this->add('checkbox', 'option_status[' . $i . ']', ts('Active?'));

      $defaultOption[$i] = $this->createElement('radio', NULL, NULL, NULL, $i);

      //for checkbox handling of default option
      $this->add('checkbox', "default_checkbox_option[$i]", NULL);
    }
    //default option selection
    $this->addGroup($defaultOption, 'default_option');
    $_showHide->addToTemplate();

    // is_display_amounts
    $this->add('checkbox', 'is_display_amounts', ts('Display Amount?'));

    // weight
    $this->add('text', 'weight', ts('Order'), CRM_Core_DAO::getAttribute('CRM_Price_DAO_PriceField', 'weight'), TRUE);
    $this->addRule('weight', ts('is a numeric field'), 'numeric');

    // checkbox / radio options per line
    $this->add('text', 'options_per_line', ts('Options Per Line'));
    $this->addRule('options_per_line', ts('must be a numeric value'), 'numeric');

    // help post, mask, attributes, javascript ?
    $this->add('textarea', 'help_post', ts('Field Help'),
      CRM_Core_DAO::getAttribute('CRM_Price_DAO_PriceField', 'help_post')
    );

    // active_on
    $date_options = array(
      'format' => 'dmY His',
      'minYear' => date('Y') - 1,
      'maxYear' => date('Y') + 5,
      'addEmptyOption' => TRUE,
    );
    $this->addDateTime('active_on', ts('Active On'), FALSE, array('formatType' => 'activityDateTime'));

    // expire_on
    $this->addDateTime('expire_on', ts('Expire On'), FALSE, array('formatType' => 'activityDateTime'));

    // is required ?
    $this->add('checkbox', 'is_required', ts('Required?'));

    // is active ?
    $this->add('checkbox', 'is_active', ts('Active?'));

    // add buttons
    $this->addButtons(array(
        array(
          'type' => 'next',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'next',
          'name' => ts('Save and New'),
          'subName' => 'new',
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
    // is public?
    $this->add('select', 'visibility_id', ts('Visibility'), CRM_Core_PseudoConstant::visibility());

    // add a form rule to check default value
    $this->addFormRule(array('CRM_Price_Form_Field', 'formRule'), $this);

    // if view mode pls freeze it with the done button.
    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->freeze();
      $url = CRM_Utils_System::url('civicrm/admin/price/field', 'reset=1&action=browse&sid=' . $this->_sid);
      $this->addElement('button',
        'done',
        ts('Done'),
        array('onclick' => "location.href='$url'")
      );
    }
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
   *   if errors then list of errors to be posted back to the form,
   *                  true otherwise
   */
  public static function formRule($fields, $files, $form) {

    // all option fields are of type "money"
    $errors = array();

    /** Check the option values entered
     *  Appropriate values are required for the selected datatype
     *  Incomplete row checking is also required.
     */
    if (($form->_action & CRM_Core_Action::ADD || $form->_action & CRM_Core_Action::UPDATE) &&
      $fields['html_type'] == 'Text' && $fields['price'] == NULL
    ) {
      $errors['price'] = ts('Price is a required field');
    }

    if (($form->_action & CRM_Core_Action::ADD || $form->_action & CRM_Core_Action::UPDATE) &&
      $fields['html_type'] == 'Text' && $fields['financial_type_id'] == ''
    ) {
      $errors['financial_type_id'] = ts('Financial Type is a required field');
    }

    //avoid the same price field label in Within PriceSet
    $priceFieldLabel = new CRM_Price_DAO_PriceField();
    $priceFieldLabel->label = $fields['label'];
    $priceFieldLabel->price_set_id = $form->_sid;

    $dupeLabel = FALSE;
    if ($priceFieldLabel->find(TRUE) && $form->_fid != $priceFieldLabel->id) {
      $dupeLabel = TRUE;
    }

    if ($dupeLabel) {
      $errors['label'] = ts('Name already exists in Database.');
    }

    if ((is_numeric(CRM_Utils_Array::value('count', $fields)) &&
        CRM_Utils_Array::value('count', $fields) == 0
      ) &&
      (CRM_Utils_Array::value('html_type', $fields) == 'Text')
    ) {
      $errors['count'] = ts('Participant Count must be greater than zero.');
    }

    if ($form->_action & CRM_Core_Action::ADD) {
      if ($fields['html_type'] != 'Text') {
        $countemptyrows = 0;
        $_flagOption = $_rowError = 0;

        $_showHide = new CRM_Core_ShowHideBlocks('', '');

        for ($index = 1; $index <= self::NUM_OPTION; $index++) {

          $noLabel = $noAmount = $noWeight = 1;
          if (!empty($fields['option_label'][$index])) {
            $noLabel = 0;
            $duplicateIndex = CRM_Utils_Array::key($fields['option_label'][$index],
              $fields['option_label']
            );

            if ((!($duplicateIndex === FALSE)) &&
              (!($duplicateIndex == $index))
            ) {
              $errors["option_label[{$index}]"] = ts('Duplicate label value');
              $_flagOption = 1;
            }
          }
          if ($form->_useForMember) {
            if (!empty($fields['membership_type_id'][$index])) {
              $memTypesIDS[] = $fields['membership_type_id'][$index];
            }
          }

          // allow for 0 value.
          if (!empty($fields['option_amount'][$index]) ||
            strlen($fields['option_amount'][$index]) > 0
          ) {
            $noAmount = 0;
          }

          if (!empty($fields['option_weight'][$index])) {
            $noWeight = 0;
            $duplicateIndex = CRM_Utils_Array::key($fields['option_weight'][$index],
              $fields['option_weight']
            );

            if ((!($duplicateIndex === FALSE)) &&
              (!($duplicateIndex == $index))
            ) {
              $errors["option_weight[{$index}]"] = ts('Duplicate weight value');
              $_flagOption = 1;
            }
          }
          if (!$noLabel && !$noAmount && !empty($fields['option_financial_type_id']) && $fields['option_financial_type_id'][$index] == '' && $fields['html_type'] != 'Text') {
            $errors["option_financial_type_id[{$index}]"] = ts('Financial Type is a Required field.');
          }
          if ($noLabel && !$noAmount) {
            $errors["option_label[{$index}]"] = ts('Label cannot be empty.');
            $_flagOption = 1;
          }

          if (!$noLabel && $noAmount) {
            $errors["option_amount[{$index}]"] = ts('Amount cannot be empty.');
            $_flagOption = 1;
          }

          if ($noLabel && $noAmount) {
            $countemptyrows++;
            $_emptyRow = 1;
          }
          elseif (!empty($fields['option_max_value'][$index]) &&
            !empty($fields['option_count'][$index]) &&
            ($fields['option_count'][$index] > $fields['option_max_value'][$index])
          ) {
            $errors["option_max_value[{$index}]"] = ts('Participant count can not be greater than max participants.');
            $_flagOption = 1;
          }

          $showBlocks = 'optionField_' . $index;
          if ($_flagOption) {
            $_showHide->addShow($showBlocks);
            $_rowError = 1;
          }

          if (!empty($_emptyRow)) {
            $_showHide->addHide($showBlocks);
          }
          else {
            $_showHide->addShow($showBlocks);
          }
          if ($index == self::NUM_OPTION) {
            $hideBlock = 'additionalOption';
            $_showHide->addHide($hideBlock);
          }

          $_flagOption = $_emptyRow = 0;
        }

        if (!empty($memTypesIDS)) {
          // check for checkboxes allowing user to select multiple memberships from same membership organization
          if ($fields['html_type'] == 'CheckBox') {
            $foundDuplicate = FALSE;
            $orgIds = array();
            foreach ($memTypesIDS as $key => $val) {
              $org = CRM_Member_BAO_MembershipType::getMembershipTypeOrganization($val);
              if (in_array($org[$val], $orgIds)) {
                $foundDuplicate = TRUE;
                break;
              }
              $orgIds[$val] = $org[$val];

            }
            if ($foundDuplicate) {
              $errors['_qf_default'] = ts('You have selected multiple memberships for the same organization or entity. Please review your selections and choose only one membership per entity.');
            }
          }

          // CRM-10390 - Only one price field in a set can include auto-renew membership options
          $foundAutorenew = FALSE;
          foreach ($memTypesIDS as $key => $val) {
            // see if any price field option values in this price field are for memberships with autorenew
            $memTypeDetails = CRM_Member_BAO_MembershipType::getMembershipTypeDetails($val);
            if (!empty($memTypeDetails['auto_renew'])) {
              $foundAutorenew = TRUE;
              break;
            }
          }

          if ($foundAutorenew) {
            // if so, check for other fields in this price set which also have auto-renew membership options
            $otherFieldAutorenew = CRM_Price_BAO_PriceSet::checkAutoRenewForPriceSet($form->_sid);
            if ($otherFieldAutorenew) {
              $errors['_qf_default'] = ts('You can include auto-renew membership choices for only one price field in a price set. Another field in this set already contains one or more auto-renew membership options.');
            }
          }
        }
        $_showHide->addToTemplate();

        if ($countemptyrows == 15) {
          $errors['option_label[1]'] = $errors['option_amount[1]'] = ts('Label and value cannot be empty.');
          $_flagOption = 1;
        }
      }
      elseif (!empty($fields['max_value']) &&
        !empty($fields['count']) &&
        ($fields['count'] > $fields['max_value'])
      ) {
        $errors['max_value'] = ts('Participant count can not be greater than max participants.');
      }

      // do not process if no option rows were submitted
      if (empty($fields['option_amount']) && empty($fields['option_label'])) {
        return TRUE;
      }

      if (empty($fields['option_name'])) {
        $fields['option_amount'] = array();
      }

      if (empty($fields['option_label'])) {
        $fields['option_label'] = array();
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form.
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->controller->exportValues('Field');

    $params['is_display_amounts'] = CRM_Utils_Array::value('is_display_amounts', $params, FALSE);
    $params['is_required'] = CRM_Utils_Array::value('is_required', $params, FALSE);
    $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
    $params['financial_type_id'] = CRM_Utils_Array::value('financial_type_id', $params, FALSE);
    if (isset($params['active_on'])) {
      $params['active_on'] = CRM_Utils_Date::processDate($params['active_on'],
        CRM_Utils_Array::value('active_on_time', $params),
        TRUE
      );
    }
    if (isset($params['expire_on'])) {
      $params['expire_on'] = CRM_Utils_Date::processDate($params['expire_on'],
        CRM_Utils_Array::value('expire_on_time', $params),
        TRUE
      );
    }
    $params['visibility_id'] = CRM_Utils_Array::value('visibility_id', $params, FALSE);
    $params['count'] = CRM_Utils_Array::value('count', $params, FALSE);

    // need the FKEY - price set id
    $params['price_set_id'] = $this->_sid;

    if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD)) {
      $fieldValues = array('price_set_id' => $this->_sid);
      $oldWeight = NULL;
      if ($this->_fid) {
        $oldWeight = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceField', $this->_fid, 'weight', 'id');
      }
      $params['weight'] = CRM_Utils_Weight::updateOtherWeights('CRM_Price_DAO_PriceField', $oldWeight, $params['weight'], $fieldValues);
    }

    // make value <=> name consistency.
    if (isset($params['option_name'])) {
      $params['option_value'] = $params['option_name'];
    }
    $params['is_enter_qty'] = CRM_Utils_Array::value('is_enter_qty', $params, FALSE);

    if ($params['html_type'] == 'Text') {
      // if html type is Text, force is_enter_qty on
      $params['is_enter_qty'] = 1;
      // modify params values as per the option group and option
      // value
      $params['option_amount'] = array(1 => $params['price']);
      $params['option_label'] = array(1 => $params['label']);
      $params['option_count'] = array(1 => $params['count']);
      $params['option_max_value'] = array(1 => CRM_Utils_Array::value('max_value', $params));
      //$params['option_description']  = array( 1 => $params['description'] );
      $params['option_weight'] = array(1 => $params['weight']);
      $params['option_financial_type_id'] = array(1 => $params['financial_type_id']);
    }

    if ($this->_fid) {
      $params['id'] = $this->_fid;
    }

    $params['membership_num_terms'] = (!empty($params['membership_type_id'])) ? CRM_Utils_Array::value('membership_num_terms', $params, 1) : NULL;

    $priceField = CRM_Price_BAO_PriceField::create($params);

    if (!is_a($priceField, 'CRM_Core_Error')) {
      CRM_Core_Session::setStatus(ts('Price Field \'%1\' has been saved.', array(1 => $priceField->label)), ts('Saved'), 'success');
    }
    $buttonName = $this->controller->getButtonName();
    $session = CRM_Core_Session::singleton();
    if ($buttonName == $this->getButtonName('next', 'new')) {
      CRM_Core_Session::setStatus(ts(' You can add another price set field.'), '', 'info');
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin/price/field', 'reset=1&action=add&sid=' . $this->_sid));
    }
    else {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin/price/field', 'reset=1&action=browse&sid=' . $this->_sid));
    }
  }

}
