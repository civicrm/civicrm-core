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
class CRM_Price_Form_Option extends CRM_Core_Form {

  /**
   * The price field id saved to the session for an update.
   *
   * @var int
   */
  protected $_fid;

  /**
   * Option value  id, used when editing the Option
   *
   * @var int
   */
  protected $_oid;

  /**
   * Array of Money fields
   *
   * @var array
   */
  protected $_moneyFields = ['amount', 'non_deductible_amount'];

  /**
   * price_set_id being edited
   *
   * @var int
   */
  protected $_sid;

  /**
   * Set variables up before form is built.
   *
   * @return void
   */
  public function preProcess() {
    $this->setPageTitle(ts('Price Option'));
    $this->_fid = CRM_Utils_Request::retrieve('fid', 'Positive',
      $this
    );
    $this->_oid = CRM_Utils_Request::retrieve('oid', 'Positive',
      $this
    );
  }

  /**
   * Set default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @return array|void  array of default values
   */
  public function setDefaultValues() {
    if ($this->_action == CRM_Core_Action::DELETE) {
      return NULL;
    }
    $defaults = [];

    if (isset($this->_oid)) {
      $params = ['id' => $this->_oid];

      CRM_Price_BAO_PriceFieldValue::retrieve($params, $defaults);

      // fix the display of the monetary value, CRM-4038
      foreach ($this->_moneyFields as $field) {
        $defaults[$field] = isset($defaults[$field]) ? CRM_Utils_Money::formatLocaleNumericRoundedByOptionalPrecision($defaults[$field], 9) : '';
      }
    }

    $extendComponentId = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $this->_sid, 'extends', 'id');

    if (!isset($defaults['membership_num_terms']) && $this->isComponentPriceOption($extendComponentId, 'CiviMember')) {
      $defaults['membership_num_terms'] = 1;
    }
    // set financial type used for price set to set default for new option
    if (!$this->_oid) {
      $defaults['financial_type_id'] = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $this->_sid, 'financial_type_id', 'id');
    }
    if (!isset($defaults['weight']) || !$defaults['weight']) {
      $fieldValues = ['price_field_id' => $this->_fid];
      $defaults['weight'] = CRM_Utils_Weight::getDefaultWeight('CRM_Price_DAO_PriceFieldValue', $fieldValues);
      $defaults['is_active'] = 1;
    }

    return $defaults;
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    if ($this->_action == CRM_Core_Action::UPDATE) {
      $finTypeId = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceFieldValue', $this->_oid, 'financial_type_id');
      if (!CRM_Financial_BAO_FinancialType::checkPermissionToEditFinancialType($finTypeId)) {
        CRM_Core_Error::statusBounce(ts("You do not have permission to access this page"));
      }
    }
    if ($this->_action == CRM_Core_Action::DELETE) {
      $this->addButtons([
        [
          'type' => 'next',
          'name' => ts('Delete'),
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]);
      return NULL;
    }
    else {
      $attributes = CRM_Core_DAO::getAttribute('CRM_Price_DAO_PriceFieldValue');
      // lets trim all the whitespace
      $this->applyFilter('__ALL__', 'trim');

      // hidden Option Id for validation use
      $this->add('hidden', 'optionId', $this->_oid);

      // Needed for i18n dialog
      $this->assign('optionId', $this->_oid);

      //hidden field ID for validation use
      $this->add('hidden', 'fieldId', $this->_fid);

      // label
      $this->add('text', 'label', ts('Option Label'), NULL, TRUE);
      $memberComponentId = CRM_Core_Component::getComponentID('CiviMember');
      if ($this->_action == CRM_Core_Action::UPDATE) {
        $this->_sid = CRM_Utils_Request::retrieve('sid', 'Positive', $this);
      }
      elseif ($this->_action == CRM_Core_Action::ADD ||
        $this->_action == CRM_Core_Action::VIEW
      ) {
        $this->_sid = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceField', $this->_fid, 'price_set_id', 'id');
      }
      $extendComponentId = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $this->_sid, 'extends', 'id');
      $this->assign('showMember', FALSE);
      if ($this->isComponentPriceOption($extendComponentId, 'CiviMember')) {
        $this->assign('showMember', TRUE);
        $membershipTypes = CRM_Member_PseudoConstant::membershipType();
        $this->add('select', 'membership_type_id', ts('Membership Type'), [
          '' => ' ',
        ] + $membershipTypes, FALSE,
        ['onClick' => "calculateRowValues( );"]);
        $this->add('number', 'membership_num_terms', ts('Number of Terms'), $attributes['membership_num_terms']);
      }
      else {
        if ($this->isComponentPriceOption($extendComponentId, 'CiviEvent')) {
          // count
          $this->add('number', 'count', ts('Participant Count'));
          $this->addRule('count', ts('Please enter a valid Max Participants.'), 'positiveInteger');

          $this->add('number', 'max_value', ts('Max Participants'));
          $this->addRule('max_value', ts('Please enter a valid Max Participants.'), 'positiveInteger');
        }

      }
      //Financial Type
      $financialType = CRM_Financial_BAO_FinancialType::getIncomeFinancialType();

      if (count($financialType)) {
        $this->assign('financialType', $financialType);
      }
      $this->add(
        'select',
        'financial_type_id',
        ts('Financial Type'),
        ['' => ts('- select -')] + $financialType,
        TRUE
      );

      //CRM_Core_DAO::getFieldValue( 'CRM_Price_DAO_PriceField', $this->_fid, 'weight', 'id' );
      // FIX ME: duplicate rule?
      /*
      $this->addRule( 'label',
      ts('Duplicate option label.'),
      'optionExists',
      array( 'CRM_Core_DAO_OptionValue', $this->_oid, $this->_ogId, 'label' ) );
       */

      // value
      $this->add('text', 'amount', ts('Option Amount'), NULL, TRUE);

      // the above value is used directly by QF, so the value has to be have a rule
      // please check with Lobo before u comment this
      $this->registerRule('amount', 'callback', 'money', 'CRM_Utils_Rule');
      $this->addRule('amount', ts('Please enter a monetary value for this field.'), 'money');

      $this->add('text', 'non_deductible_amount', ts('Non-deductible Amount'), NULL);
      $this->registerRule('non_deductible_amount', 'callback', 'money', 'CRM_Utils_Rule');
      $this->addRule('non_deductible_amount', ts('Please enter a monetary value for this field.'), 'money');

      $this->add('textarea', 'description', ts('Description'));
      $this->add('textarea', 'help_pre', ts('Pre Option Help'));
      $this->add('textarea', 'help_post', ts('Post Option Help'));

      // weight
      $this->add('number', 'weight', ts('Order'), NULL, TRUE);
      $this->addRule('weight', ts('is a numeric field'), 'numeric');

      // is active ?
      $this->add('checkbox', 'is_active', ts('Active?'));

      // is public?
      $this->add('select', 'visibility_id', ts('Visibility'), CRM_Core_PseudoConstant::visibility());

      //is default
      $this->add('checkbox', 'is_default', ts('Default'));

      if ($this->_fid) {
        //hide the default checkbox option for text field
        $htmlType = CRM_Core_DAO::getFieldValue('CRM_Price_BAO_PriceField', $this->_fid, 'html_type');
        $this->assign('hideDefaultOption', FALSE);
        if ($htmlType == 'Text') {
          $this->assign('hideDefaultOption', TRUE);
        }
      }
      // add buttons
      $this->addButtons([
        [
          'type' => 'next',
          'name' => ts('Save'),
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]);

      // if view mode pls freeze it with the done button.
      if ($this->_action & CRM_Core_Action::VIEW) {
        $this->freeze();
        $this->addButtons([
          [
            'type' => 'cancel',
            'name' => ts('Done'),
            'isDefault' => TRUE,
          ],
        ]);
      }
    }

    $this->addFormRule(['CRM_Price_Form_Option', 'formRule'], $this);
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
    $errors = [];
    if (!empty($fields['count']) && !empty($fields['max_value']) &&
      $fields['count'] > $fields['max_value']
    ) {
      $errors['count'] = ts('Participant count can not be greater than max participants.');
    }

    $priceField = CRM_Price_BAO_PriceField::findById($fields['fieldId']);
    $visibilityOptions = CRM_Core_PseudoConstant::get('CRM_Price_BAO_PriceFieldValue', 'visibility_id', ['labelColumn' => 'name']);

    $publicCount = 0;
    $options = CRM_Price_BAO_PriceField::getOptions($priceField->id);
    foreach ($options as $currentOption) {
      if ($fields['optionId'] == $currentOption['id'] && $visibilityOptions[$fields['visibility_id']] == 'public') {
        $publicCount++;
      }
      elseif ($fields['optionId'] != $currentOption['id'] && $visibilityOptions[$currentOption['visibility_id']] == 'public') {
        $publicCount++;
      }
    }
    if ($visibilityOptions[$priceField->visibility_id] == 'public' && $publicCount == 0 && $visibilityOptions[$fields['visibility_id']] == 'admin') {
      $errors['visibility_id'] = ts('All other options for this \'Public\' field have \'Admin\' visibility. There should at least be one \'Public\' option, or make the field \'Admin\' only.');
    }
    elseif ($visibilityOptions[$priceField->visibility_id] == 'admin' && $visibilityOptions[$fields['visibility_id']] == 'public') {
      $errors['visibility_id'] = ts('You must choose \'Admin\' visibility for this price option, as it belongs to a field with \'Admin\' visibility.');
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form.
   *
   * @return void
   */
  public function postProcess() {
    if ($this->_action == CRM_Core_Action::DELETE) {
      $fieldValues = ['price_field_id' => $this->_fid];
      $wt = CRM_Utils_Weight::delWeight('CRM_Price_DAO_PriceFieldValue', $this->_oid, $fieldValues);
      $label = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceFieldValue',
        $this->_oid,
        'label', 'id'
      );

      CRM_Price_BAO_PriceFieldValue::deleteRecord(['id' => $this->_oid]);
      CRM_Core_Session::setStatus(ts('%1 option has been deleted.', [1 => $label]), ts('Record Deleted'), 'success');
      return NULL;
    }
    else {
      $params = $this->controller->exportValues('Option');

      foreach ($this->_moneyFields as $field) {
        $params[$field] = CRM_Utils_Rule::cleanMoney(trim($params[$field]));
      }
      $params['price_field_id'] = $this->_fid;
      $params['is_default'] ??= FALSE;
      $params['is_active'] ??= FALSE;
      $params['visibility_id'] ??= FALSE;
      $ids = [];
      if ($this->_oid) {
        $params['id'] = $this->_oid;
      }
      $optionValue = CRM_Price_BAO_PriceFieldValue::create($params, $ids);

      CRM_Core_Session::setStatus(ts("The option '%1' has been saved.", [1 => $params['label']]), ts('Value Saved'), 'success');
    }
  }

  /**
   * Check to see if this is within a price set that supports the specific component
   * @var string $extends separator encoded string of Component ids
   * @var string $component Component name
   *
   * @return bool
   */
  private function isComponentPriceOption(string $extends, string $component): bool {
    if (!CRM_Core_Component::isEnabled($component)) {
      return FALSE;
    }
    $extends = CRM_Core_DAO::unSerializeField($extends, CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND);
    return in_array(CRM_Core_Component::getComponentID($component), $extends, FALSE);
  }

}
