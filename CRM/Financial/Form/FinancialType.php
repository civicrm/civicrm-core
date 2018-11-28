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
 */

/**
 * This class generates form components for Financial Type
 */
class CRM_Financial_Form_FinancialType extends CRM_Contribute_Form {

  use CRM_Core_Form_EntityFormTrait;

  /**
   * Fields for the entity to be assigned to the template.
   *
   * @var array
   */
  protected $entityFields = [];

  /**
   * Deletion message to be assigned to the form.
   *
   * @var string
   */
  protected $deleteMessage;

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    // Check permission for Financial Type when ACL-FT is enabled
    if (CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus()
      && !CRM_Core_Permission::check('administer CiviCRM Financial Types')
    ) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this page.'));
    }
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
      'name' => [
        'name' => 'name',
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
    $this->deleteMessage = ts('WARNING: You cannot delete a financial type if it is currently used by any Contributions, Contribution Pages or Membership Types. Consider disabling this option instead.') . ts('Deleting a financial type cannot be undone.') . ts('Do you want to continue?');
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    self::buildQuickEntityForm();
    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }
    if ($this->_action == CRM_Core_Action::UPDATE && CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialType', $this->_id, 'is_reserved')) {
      $this->freeze(['is_active']);
    }
    $this->addRule('name', ts('A financial type with this name already exists. Please select another name.'), 'objectExists',
      ['CRM_Financial_DAO_FinancialType', $this->_id]
    );
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      $errors = CRM_Financial_BAO_FinancialType::del($this->_id);
      if (!empty($errors)) {
        CRM_Core_Error::statusBounce($errors['error_message'], CRM_Utils_System::url('civicrm/admin/financial/financialType', "reset=1&action=browse"), ts('Cannot Delete'));
      }
      CRM_Core_Session::setStatus(ts('Selected financial type has been deleted.'), ts('Record Deleted'), 'success');
    }
    else {
      // store the submitted values in an array
      $params = $this->exportValues();
      if ($this->_id) {
        $params['id'] = $this->_id;
      }
      foreach ([
        'is_active',
        'is_reserved',
        'is_deductible',
      ] as $field) {
        $params[$field] = CRM_Utils_Array::value($field, $params, FALSE);
      }
      $financialType = civicrm_api3('FinancialType', 'create', $params);
      if ($this->_action & CRM_Core_Action::UPDATE) {
        $url = CRM_Utils_System::url('civicrm/admin/financial/financialType', 'reset=1&action=browse');
        CRM_Core_Session::setStatus(ts('The financial type "%1" has been updated.', [1 => $params['name']]), ts('Saved'), 'success');
      }
      else {
        $url = CRM_Utils_System::url('civicrm/admin/financial/financialType/accounts', 'reset=1&action=browse&aid=' . $financialType['id']);

        $statusArray = [
          1 => $params['name'],
        ];
        $financialAccounts = civicrm_api3('EntityFinancialAccount', 'get', [
          'return' => ["financial_account_id.name"],
          'entity_table' => "civicrm_financial_type",
          'entity_id' => $financialType['id'],
          'options' => ['sort' => "id"],
          'account_relationship' => ['!=' => "Income Account is"],
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
        CRM_Core_Session::setStatus($text, ts('Saved'), 'success', ['expires' => 0]);
      }

      $session = CRM_Core_Session::singleton();
      $session->replaceUserContext($url);
    }
  }

}
