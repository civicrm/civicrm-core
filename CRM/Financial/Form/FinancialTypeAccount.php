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

use Civi\Api4\EntityFinancialAccount;

/**
 * This class generates form components for Financial Type Account
 */
class CRM_Financial_Form_FinancialTypeAccount extends CRM_Core_Form {

  /**
   * The financial type id saved to the session for an update.
   *
   * @var int
   */
  protected $_aid;

  /**
   * The financial type accounts id, used when editing the field
   *
   * @var int
   */
  protected $_id;

  /**
   * The name of the BAO object for this form.
   *
   * @var string
   */
  protected $_BAOName;

  /**
   * Flag if its a AR account type.
   *
   * @var bool
   */
  protected $_isARFlag = FALSE;

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    $this->_aid = CRM_Utils_Request::retrieve('aid', 'Positive', $this);
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);

    if (!$this->_id && ($this->_action & CRM_Core_Action::UPDATE)) {
      $this->_id = CRM_Utils_Type::escape($this->_id, 'Positive');
    }
    $url = CRM_Utils_System::url('civicrm/admin/financial/financialType/accounts',
      "reset=1&action=browse&aid={$this->_aid}");

    $this->_BAOName = 'CRM_Financial_BAO_EntityFinancialAccount';
    if ($this->_aid && ($this->_action & CRM_Core_Action::ADD)) {
      $this->_title = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialType', $this->_aid, 'name');
      $this->setTitle($this->_title . ' - ' . ts('Financial Accounts'));

      $session = CRM_Core_Session::singleton();
      $session->pushUserContext($url);
    }
    // CRM-12492
    if (!($this->_action & CRM_Core_Action::ADD)) {
      $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Accounts Receivable Account is' "));
      $accountRelationship = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_EntityFinancialAccount', $this->_id, 'account_relationship');
      if ($accountRelationship == $relationTypeId) {
        $this->_isARFlag = TRUE;
        if ($this->_action & CRM_Core_Action::DELETE) {
          CRM_Core_Session::setStatus(ts("Selected financial type account with 'Accounts Receivable Account is' account relationship cannot be deleted."),
            '', 'error');
          CRM_Utils_System::redirect($url);
        }
      }
    }
    if ($this->_id) {
      $financialAccount = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_EntityFinancialAccount', $this->_id, 'financial_account_id');
      $fieldTitle = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialAccount', $financialAccount, 'name');
      $this->setTitle($fieldTitle . ' - ' . ts('Financial Type Accounts'));
    }

    $breadCrumb = [
      [
        'title' => ts('Financial Type Accounts'),
        'url' => $url,
      ],
    ];
    CRM_Utils_System::appendBreadCrumb($breadCrumb);
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    if ($this->_action & CRM_Core_Action::VIEW || $this->_action & CRM_Core_Action::PREVIEW) {
      $this->addButtons([
        [
          'type' => 'cancel',
          'name' => ts('Done'),
          'isDefault' => TRUE,
        ],
      ]);
    }
    else {
      $this->addButtons([
        [
          'type' => 'next',
          'name' => $this->_action & CRM_Core_Action::DELETE ? ts('Delete') : ts('Save'),
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]);
    }
    $this->setPageTitle(ts('Financial Type Account'));

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    if (isset($this->_id)) {
      $params = ['id' => $this->_id];
      CRM_Financial_BAO_EntityFinancialAccount::retrieve($params, $defaults);
      $this->setDefaults($defaults);
      $financialAccountTitle = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialAccount', $defaults['financial_account_id'], 'name');
    }

    $this->applyFilter('__ALL__', 'trim');

    if ($this->_action == CRM_Core_Action::UPDATE) {
      $this->assign('aid', $this->_id);
      // hidden field to catch the group id in profile
      $this->add('hidden', 'financial_type_id', $this->_aid);

      // hidden field to catch the field id in profile
      $this->add('hidden', 'account_type_id', $this->_id);
    }
    $params['orderColumn'] = 'label';
    $AccountTypeRelationship = CRM_Core_PseudoConstant::get('CRM_Financial_DAO_EntityFinancialAccount', 'account_relationship', $params);
    if (!empty($AccountTypeRelationship)) {
      $element = $this->add('select',
        'account_relationship',
        ts('Financial Account Relationship'),
        ['select' => ts('- Select Financial Account Relationship -')] + $AccountTypeRelationship,
        TRUE
      );
    }

    if ($this->_isARFlag) {
      $element->freeze();
    }

    if ($this->_action == CRM_Core_Action::ADD) {
      if (!empty($this->_submitValues['account_relationship']) || !empty($this->_submitValues['financial_account_id'])) {
        $financialAccountType = CRM_Financial_BAO_FinancialAccount::getfinancialAccountRelations();
        $financialAccountType = $financialAccountType[$this->_submitValues['account_relationship']] ?? NULL;
        $result = \Civi\Api4\FinancialAccount::get()
          ->addSelect('id', 'label')
          ->addWhere('financial_account_type_id', '=', $financialAccountType)
          ->addWhere('is_active', '=', TRUE)
          ->addOrderBy('label')
          ->execute()
          ->column('label', 'id');

        $financialAccountSelect = ['' => ts('- select -')] + $result;
      }
      else {
        $result = \Civi\Api4\FinancialAccount::get()
          ->addSelect('id', 'label')
          ->addWhere('is_active', '=', TRUE)
          ->addOrderBy('label')
          ->execute()
          ->column('label', 'id');
        $financialAccountSelect = [
          'select' => ts('- select -'),
        ] + $result;
      }
    }
    if ($this->_action == CRM_Core_Action::UPDATE) {
      $financialAccountType = CRM_Financial_BAO_FinancialAccount::getfinancialAccountRelations();
      $financialAccountType = $financialAccountType[$this->_defaultValues['account_relationship']];
      $result = \Civi\Api4\FinancialAccount::get()
        ->addSelect('id', 'label')
        ->addWhere('financial_account_type_id', '=', $financialAccountType)
        ->addWhere('is_active', '=', TRUE)
        ->addOrderBy('label')
        ->execute()
        ->column('label', 'id');

      $financialAccountSelect = ['' => ts('- select -')] + $result;
    }

    $this->add('select',
      'financial_account_id',
      ts('Financial Account'),
      $financialAccountSelect,
      TRUE
    );

    $this->addFormRule(['CRM_Financial_Form_FinancialTypeAccount', 'formRule'], $this);
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
    $errorFlag = FALSE;
    if ($self->_action == CRM_Core_Action::DELETE) {
      $relationValues = CRM_Financial_DAO_EntityFinancialAccount::buildOptions('account_relationship');
      if (($values['financial_account_id'] ?? NULL) != 'select') {
        if ($relationValues[$values['account_relationship']] == 'Premiums Inventory Account is' || $relationValues[$values['account_relationship']] == 'Cost of Sales Account is') {
          $premiumsProduct = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_PremiumsProduct', $values['financial_type_id'], 'product_id', 'financial_type_id');
          $product = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Product', $values['financial_type_id'], 'name', 'financial_type_id');
          if (!empty($premiumsProduct) || !empty($product)) {
            $errorMsg['account_relationship'] = 'You cannot remove ' . $relationValues[$values['account_relationship']] . ' relationship while the Financial Type is used for a Premium.';
          }
        }
      }
    }
    if (($values['account_relationship'] ?? NULL) == 'select') {
      $errorMsg['account_relationship'] = 'Financial Account relationship is a required field.';
    }
    if (($values['financial_account_id'] ?? NULL) == 'select') {
      $errorMsg['financial_account_id'] = 'Financial Account is a required field.';
    }
    if (!empty($values['account_relationship']) && !empty($values['financial_account_id'])) {
      $params = [
        'account_relationship' => $values['account_relationship'],
        'entity_id' => $self->_aid,
        'entity_table' => 'civicrm_financial_type',
      ];
      $defaults = [];
      if ($self->_action == CRM_Core_Action::ADD) {
        // @todo replace with call to CRM_Financial_BAO_FinancialAccount getSalesTaxFinancialAccount
        $relationshipId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Sales Tax Account is' "));
        $isTax = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialAccount', $values['financial_account_id'], 'is_tax');
        if ($values['account_relationship'] == $relationshipId) {
          if (!($isTax)) {
            $errorMsg['financial_account_id'] = ts('Is Tax? must be set for respective financial account');
          }
        }
        $result = CRM_Financial_BAO_EntityFinancialAccount::retrieve($params, $defaults);
        if ($result) {
          $errorFlag = TRUE;
        }
      }
      if ($self->_action == CRM_Core_Action::UPDATE) {
        if ($values['account_relationship'] == $self->_defaultValues['account_relationship'] && $values['financial_account_id'] == $self->_defaultValues['financial_account_id']) {
          $errorFlag = FALSE;
        }
        else {
          $params['financial_account_id'] = $values['financial_account_id'];
          $result = CRM_Financial_BAO_EntityFinancialAccount::retrieve($params, $defaults);
          if ($result) {
            $errorFlag = TRUE;
          }
        }
      }

      if ($errorFlag) {
        $errorMsg['account_relationship'] = ts('This account relationship already exits');
      }
    }
    return CRM_Utils_Array::crmIsEmptyArray($errorMsg) ? TRUE : $errorMsg;
  }

  /**
   * Process the form submission.
   */
  public function postProcess(): void {
    if ($this->_action & CRM_Core_Action::DELETE) {
      try {
        EntityFinancialAccount::delete()->addWhere('id', '=', $this->_id)->execute();
      }
      catch (CRM_Core_Exception $e) {
        CRM_Core_Session::setStatus($e->getMessage());
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/financial/financialType/accounts', "reset=1&action=browse&aid={$this->_aid}"));
      }
      CRM_Core_Session::setStatus(ts('Selected financial type account has been deleted.'));
    }
    else {
      $ids = [];
      // store the submitted values in an array
      $params = $this->exportValues();

      if ($this->_action & CRM_Core_Action::UPDATE) {
        $ids['entityFinancialAccount'] = $this->_id;
      }
      if ($this->_action & CRM_Core_Action::ADD || $this->_action & CRM_Core_Action::UPDATE) {
        $params['financial_account_id'] = $this->_submitValues['financial_account_id'];
      }
      $params['entity_table'] = 'civicrm_financial_type';
      if ($this->_action & CRM_Core_Action::ADD) {
        $params['entity_id'] = $this->_aid;
      }
      try {
        $financialTypeAccount = CRM_Financial_BAO_EntityFinancialAccount::add($params, $ids);
        CRM_Core_Session::setStatus(ts('The financial type Account has been saved.'), ts('Saved'), 'success');
      }
      catch (CRM_Core_Exception $e) {
        CRM_Core_Error::statusBounce($e->getMessage());
      }
    }

    $buttonName = $this->controller->getButtonName();
    $session = CRM_Core_Session::singleton();

    if ($buttonName == $this->getButtonName('next', 'new')) {
      CRM_Core_Session::setStatus(ts(' You can add another Financial Account Type.'));
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin/financial/financialType/accounts',
        "reset=1&action=add&aid={$this->_aid}"));
    }
    else {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin/financial/financialType/accounts',
        "reset=1&action=browse&aid={$this->_aid}"));
    }
  }

}
