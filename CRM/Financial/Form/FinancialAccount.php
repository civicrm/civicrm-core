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
 * This class generates form components for Financial Account
 */
class CRM_Financial_Form_FinancialAccount extends CRM_Contribute_Form {

  /**
   * Flag if its a AR account type.
   *
   * @var boolean
   */
  protected $_isARFlag = FALSE;


  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    parent::preProcess();

    if ($this->_id) {
      $params = [
        'id' => $this->_id,
      ];
      $financialAccount = CRM_Financial_BAO_FinancialAccount::retrieve($params, CRM_Core_DAO::$_nullArray);
      $financialAccountTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('financial_account_type', NULL, " AND v.name LIKE 'Asset' "));
      if ($financialAccount->financial_account_type_id == $financialAccountTypeId
        && strtolower($financialAccount->account_type_code) == 'ar'
        && !CRM_Financial_BAO_FinancialAccount::getARAccounts($this->_id, $financialAccountTypeId)
      ) {
        $this->_isARFlag = TRUE;
        if ($this->_action & CRM_Core_Action::DELETE) {
          $msg = ts("The selected financial account cannot be deleted because at least one Accounts Receivable type account is required (to ensure that accounting transactions are in balance).");
          CRM_Core_Error::statusBounce($msg);
        }
      }
    }
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    $this->setPageTitle(ts('Financial Account'));

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $this->applyFilter('__ALL__', 'trim');
    $attributes = CRM_Core_DAO::getAttribute('CRM_Financial_DAO_FinancialAccount');
    $this->add('text', 'name', ts('Name'), $attributes['name'], TRUE);
    $this->addRule('name', ts('A financial type with this name already exists. Please select another name.'),
      'objectExists', ['CRM_Financial_DAO_FinancialAccount', $this->_id]);

    $this->add('text', 'description', ts('Description'), $attributes['description']);
    $this->add('text', 'accounting_code', ts('Accounting Code'), $attributes['accounting_code']);
    $elementAccounting = $this->add('text', 'account_type_code', ts('Account Type Code'), $attributes['account_type_code']);
    $this->addEntityRef('contact_id', ts('Owner'), [
      'api' => ['params' => ['contact_type' => 'Organization']],
      'create' => TRUE,
    ]);
    $this->add('text', 'tax_rate', ts('Tax Rate'), $attributes['tax_rate']);
    $this->add('checkbox', 'is_deductible', ts('Tax-Deductible?'));
    $elementActive = $this->add('checkbox', 'is_active', ts('Enabled?'));
    $this->add('checkbox', 'is_tax', ts('Is Tax?'));

    $element = $this->add('checkbox', 'is_default', ts('Default?'));
    // CRM-12470 freeze is default if is_default is set
    if ($this->_id && CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialAccount', $this->_id, 'is_default')) {
      $element->freeze();
    }

    $financialAccountType = CRM_Core_PseudoConstant::get('CRM_Financial_DAO_FinancialAccount', 'financial_account_type_id');
    if (!empty($financialAccountType)) {
      $element = $this->add('select', 'financial_account_type_id', ts('Financial Account Type'),
        ['' => '- select -'] + $financialAccountType, TRUE, ['class' => 'crm-select2 huge']);
      if ($this->_isARFlag) {
        $element->freeze();
        $elementAccounting->freeze();
        $elementActive->freeze();
      }
      elseif ($this->_id && CRM_Financial_BAO_FinancialAccount::validateFinancialAccount($this->_id)) {
        $element->freeze();
      }
    }

    if ($this->_action == CRM_Core_Action::UPDATE &&
      CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialAccount', $this->_id, 'is_reserved')
    ) {
      $this->freeze(['name', 'description', 'is_active']);
    }
    $this->addFormRule(['CRM_Financial_Form_FinancialAccount', 'formRule'], $this);
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $values
   *   posted values of the form
   * @param $files
   * @param $self
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
      if (CRM_Utils_Array::value('tax_rate', $values) == NULL) {
        $errorMsg['tax_rate'] = ts('Please enter value for tax rate');
      }
    }
    if ((CRM_Utils_Array::value('tax_rate', $values) != NULL)) {
      if ($values['tax_rate'] < 0 || $values['tax_rate'] >= 100) {
        $errorMsg['tax_rate'] = ts('Tax Rate Should be between 0 - 100');
      }
    }
    if ($self->_action & CRM_Core_Action::UPDATE) {
      if (!(isset($values['is_tax']))) {
        $relationshipId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Sales Tax Account is' "));
        $params = [
          'financial_account_id' => $self->_id,
          'account_relationship' => $relationshipId,
        ];
        $result = CRM_Financial_BAO_FinancialTypeAccount::retrieve($params, $defaults);
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
      $defaults['contact_id'] = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Domain', CRM_Core_Config::domainID(), 'contact_id');
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

      if ($this->_action & CRM_Core_Action::UPDATE) {
        $params['id'] = $this->_id;
      }
      foreach ([
        'is_active',
        'is_deductible',
        'is_tax',
        'is_default',
      ] as $field) {
        $params[$field] = CRM_Utils_Array::value($field, $params, FALSE);
      }
      $financialAccount = CRM_Financial_BAO_FinancialAccount::add($params);
      CRM_Core_Session::setStatus(ts('The Financial Account \'%1\' has been saved.', [1 => $financialAccount->name]), ts('Saved'), 'success');
    }
  }

}
