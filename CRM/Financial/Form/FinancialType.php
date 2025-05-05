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
 * This class generates form components for Financial Type
 */
class CRM_Financial_Form_FinancialType extends CRM_Core_Form {

  use CRM_Core_Form_EntityFormTrait;

  protected $_BAOName = 'CRM_Financial_BAO_FinancialType';

  /**
   * Set variables up before form is built.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    parent::preProcess();
    $this->setPageTitle(ts('Financial Type'));
    if ($this->_id) {
      $this->_title = CRM_Core_PseudoConstant::getLabel(
        'CRM_Financial_BAO_FinancialType',
        'financial_type',
        $this->_id
      );
      $this->assign('aid', $this->_id);
    }
  }

  /**
   * Set entity fields to be assigned to the form.
   */
  protected function setEntityFields() {
    $this->entityFields = [
      'label' => [
        'name' => 'label',
        'required' => TRUE,
      ],
      'description' => ['name' => 'description'],
      'is_deductible' => [
        'name' => 'is_deductible',
        'description' => ts('Are contributions of this type tax-deductible?'),
      ],
      'is_reserved' => ['name' => 'is_reserved'],
      'is_active' => ['name' => 'is_active'],
    ];
  }

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'FinancialType';
  }

  /**
   * Set the delete message.
   *
   * We do this from the constructor in order to do a translation.
   */
  public function setDeleteMessage() {
    $this->deleteMessage = implode(
      ' ',
      [
        ts('WARNING: You cannot delete a financial type if it is currently used by any Contributions, Contribution Pages or Membership Types. Consider disabling this option instead.'),
        ts('Deleting a financial type cannot be undone.'),
        ts('Do you want to continue?'),
      ]
    );
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm() {
    $this->buildQuickEntityForm();
    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }
    if ($this->_action == CRM_Core_Action::UPDATE && CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialType', $this->_id, 'is_reserved')) {
      $this->freeze(['is_active']);
    }
    $this->addRule('label', ts('A financial type with this label already exists. Please select another label.'), 'objectExists',
      ['CRM_Financial_DAO_FinancialType', $this->_id]
    );
  }

  /**
   * Process the form submission.
   *
   * @throws \CRM_Core_Exception
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      try {
        CRM_Financial_BAO_FinancialType::deleteRecord(['id' => $this->_id]);
      }
      catch (CRM_Core_Exception $e) {
        CRM_Core_Error::statusBounce($e->getMessage(), CRM_Utils_System::url('civicrm/admin/financial/financialType', "reset=1&action=browse"), ts('Cannot Delete'));
      }
      CRM_Core_Session::setStatus(ts('Selected financial type has been deleted.'), ts('Record Deleted'), 'success');
    }
    else {
      // store the submitted values in an array
      $params = $this->exportValues();
      if ($this->_id) {
        $params['id'] = $this->_id;
      }
      foreach (['is_active', 'is_reserved', 'is_deductible'] as $field) {
        $params[$field] ??= FALSE;
      }
      $params['custom'] = CRM_Core_BAO_CustomField::postProcess($params,
        $params['id'] ?? NULL,
        'FinancialType'
      );
      $financialType = (array) CRM_Financial_BAO_FinancialType::writeRecord($params);
      if ($this->_action & CRM_Core_Action::UPDATE) {
        $url = CRM_Utils_System::url('civicrm/admin/financial/financialType', 'reset=1&action=browse');
        CRM_Core_Session::setStatus(ts('The financial type "%1" has been updated.', [1 => $params['label']]), ts('Saved'), 'success');
      }
      else {
        $url = CRM_Utils_System::url('civicrm/admin/financial/financialType/accounts', 'reset=1&action=browse&aid=' . $financialType['id']);

        $statusArray = [
          1 => $params['label'],
        ];
        $financialAccounts = civicrm_api3('EntityFinancialAccount', 'get', [
          'return' => ['financial_account_id.name'],
          'entity_table' => 'civicrm_financial_type',
          'entity_id' => $financialType['id'],
          'options' => ['sort' => "id"],
          'account_relationship' => ['!=' => 'Income Account is'],
        ]);
        if (!empty($financialAccounts['values'])) {
          foreach ($financialAccounts['values'] as $financialAccount) {
            $statusArray[] = $financialAccount['financial_account_id.name'];
          }
          $text = ts('Your Financial "%1" Type has been created, along with a corresponding income account "%1". That income account, along with standard financial accounts "%2", "%3" and "%4" have been linked to the financial type. You may edit or replace those relationships here.', $statusArray);
        }
        else {
          $text = ts('Your Financial "%1" Type has been created and assigned to an existing financial account with the same title. You should review the assigned account and determine whether additional account relationships are needed.', $statusArray);
        }
        CRM_Core_Session::setStatus($text, ts('Saved'), 'success', ['expires' => 30000]);
      }

      $session = CRM_Core_Session::singleton();
      $session->replaceUserContext($url);
    }
  }

  /**
   * Set default values for the form. MobileProvider that in edit/view mode
   * the default values are retrieved from the database
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = $this->getEntityDefaults();

    if ($this->_action & CRM_Core_Action::ADD) {
      $defaults['is_active'] = 1;
    }
    return $defaults;
  }

}
