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
 * This class generates form components for Financial Account
 */
class CRM_Financial_Form_FinancialAccount extends CRM_Contribute_Form {
  use CRM_Core_Form_EntityFormTrait;
  use CRM_Custom_Form_CustomDataTrait;

  /**
   * Flag if its a AR account type.
   *
   * @var bool
   */
  protected $_isARFlag = FALSE;

  /**
   * Explicitly declare the entity api name.
   *
   * @return string
   */
  public function getDefaultEntity() {
    return 'FinancialAccount';
  }

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    parent::preProcess();

    if ($this->_id) {
      $params = [
        'id' => $this->_id,
      ];
      $financialAccount = CRM_Financial_BAO_FinancialAccount::retrieve($params);
      $financialAccountTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('financial_account_type', NULL, " AND v.name LIKE 'Asset' "));
      if ($financialAccount->financial_account_type_id == $financialAccountTypeId
        && strtolower($financialAccount->account_type_code) === 'ar'
        && !CRM_Financial_BAO_FinancialAccount::getARAccounts($this->_id, $financialAccountTypeId)
      ) {
        $this->_isARFlag = TRUE;
        if ($this->_action & CRM_Core_Action::DELETE) {
          $msg = ts("The selected financial account cannot be deleted because at least one Accounts Receivable type account is required (to ensure that accounting transactions are in balance).");
          CRM_Core_Error::statusBounce($msg);
        }
      }
    }
    // Assigned for the ajax call to get custom data.
    $this->assign('entityID', $this->_id);
  }

  /**
   * Set entity fields to be assigned to the form.
   */
  protected function setEntityFields() {
    $this->entityFields = [
      'label' => [
        'name' => 'label',
        'required' => TRUE,
        'label' => ts('Label'),
      ],
      'description' => [
        'name' => 'description',
        'label' => ts('Description'),
      ],
      'contact_id' => [
        'name' => 'contact_id',
        'description' => ts('Use this field to indicate the organization that owns this account.'),
        'help' => ['id' => 'contact_id'],
        'label' => ts('Owner'),
      ],
      'financial_account_type_id' => [
        'name' => 'financial_account_type_id',
        'required' => TRUE,
      ],
      'accounting_code' => [
        'name' => 'accounting_code',
        'description' => ts('Enter the corresponding account code used in your accounting system. This code will be available for contribution export, and included in accounting batch exports.'),
      ],
      'account_type_code' => [
        'name' => 'account_type_code',
        'description' => ts('Enter an account type code for this account. Account type codes are required for QuickBooks integration and will be included in all accounting batch exports.'),
        'help' => ['id' => 'account_type_code'],
      ],
      'is_deductible' => [
        'name' => 'is_deductible',
        'description' => ts('Are transactions of this type tax-deductible?'),
        'label' => ts('Tax-Deductible?'),
      ],
      'is_active' => ['name' => 'is_active'],
      'is_tax' => [
        'name' => 'is_tax',
        'label' => ts('Is Tax?'),
      ],
      'tax_rate' => [
        'name' => 'tax_rate',
        'description' => ts('The default rate used to calculate the taxes collected into this account (e.g. for tax rate of 8.27%, enter 8.27).'),
        'label' => ts('Tax Rate'),
      ],
      'is_default' => [
        'name' => 'is_default',
        'description' => ts('If selected, this account will be used as the default for any financial transactions that do not have a specific financial account assigned. Note: only one financial account can be set as the default.'),
      ],
    ];
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    // CRM-12470 freeze is default if is_default is set
    if ($this->_id && CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialAccount', $this->_id, 'is_default')) {
      $this->entityFields['is_default']['is_freeze'] = TRUE;
    }

    if ($this->_isARFlag) {
      $this->entityFields['financial_account_type_id']['is_freeze'] = TRUE;
      $this->entityFields['account_type_code']['is_freeze'] = TRUE;
      $this->entityFields['is_active']['is_freeze'] = TRUE;
    }
    elseif ($this->_id && CRM_Financial_BAO_FinancialAccount::validateFinancialAccount($this->_id)) {
      $this->entityFields['financial_account_type_id']['is_freeze'] = TRUE;
    }

    if ($this->_action == CRM_Core_Action::UPDATE &&
      CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialAccount', $this->_id, 'is_reserved')
    ) {
      $this->entityFields['is_active']['is_freeze'] = TRUE;
    }
    $this->buildQuickEntityForm();
    $this->addRule('label', ts('A financial type with this label already exists. Please select another label.'),
      'objectExists', ['CRM_Financial_DAO_FinancialAccount', $this->_id]);
    $this->addFormRule(['CRM_Financial_Form_FinancialAccount', 'formRule'], $this);
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $values
   *   posted values of the form
   * @param $files
   * @param self $self
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($values, $files, $self) {
    $errorMsg = [];
    $financialAccountTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('financial_account_type', NULL, " AND v.name LIKE 'Liability' "));
    if (isset($values['is_tax'])) {
      if ($values['financial_account_type_id'] != $financialAccountTypeId) {
        $errorMsg['financial_account_type_id'] = ts('Taxable accounts should have Financial Account Type set to Liability.');
      }
      if (!isset($values['tax_rate'])) {
        $errorMsg['tax_rate'] = ts('Please enter value for tax rate');
      }
    }
    if ((($values['tax_rate'] ?? NULL) != NULL)) {
      if ($values['tax_rate'] < 0 || $values['tax_rate'] >= 100) {
        $errorMsg['tax_rate'] = ts('Tax Rate Should be between 0 - 100');
      }
    }
    if ($self->_action & CRM_Core_Action::UPDATE) {
      if (!(isset($values['is_tax']))) {
        // @todo replace with call to CRM_Financial_BAO_FinancialAccount getSalesTaxFinancialAccount
        $relationshipId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Sales Tax Account is' "));
        $params = [
          'financial_account_id' => $self->_id,
          'account_relationship' => $relationshipId,
        ];
        $result = CRM_Financial_BAO_EntityFinancialAccount::retrieve($params, $defaults);
        if ($result) {
          $errorMsg['is_tax'] = ts('Is Tax? must be set for this financial account');
        }
      }
    }
    return CRM_Utils_Array::crmIsEmptyArray($errorMsg) ? TRUE : $errorMsg;
  }

  /**
   * Set default values for the form.
   * the default values are retrieved from the database.
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();
    if ($this->_action & CRM_Core_Action::ADD) {
      $defaults['contact_id'] = CRM_Core_BAO_Domain::getDomain()->contact_id;
    }
    return $defaults;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      if (CRM_Financial_BAO_FinancialAccount::del($this->_id)) {
        CRM_Core_Session::setStatus(ts('Selected Financial Account has been deleted.'));
      }
      else {
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/financial/financialAccount', "reset=1&action=browse"));
      }
    }
    else {
      // store the submitted values in an array
      $params = $this->exportValues();
      $params['custom'] = CRM_Core_BAO_CustomField::postProcess($this->getSubmittedValues(), $this->_id, 'FinancialAccount');

      if ($this->_action & CRM_Core_Action::UPDATE) {
        $params['id'] = $this->_id;
      }
      foreach (['is_active', 'is_deductible', 'is_tax', 'is_default'] as $field) {
        $params[$field] ??= FALSE;
      }
      $financialAccount = CRM_Financial_BAO_FinancialAccount::writeRecord($params);
      CRM_Core_Session::setStatus(ts('The Financial Account \'%1\' has been saved.', [1 => $financialAccount->label]), ts('Saved'), 'success');
    }
  }

}
