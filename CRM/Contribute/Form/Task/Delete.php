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
 */

/**
 * This class provides the functionality to delete a group of contributions.
 *
 * This class provides functionality for the actual deletion.
 */
class CRM_Contribute_Form_Task_Delete extends CRM_Contribute_Form_Task {

  /**
   * Are we operating in "single mode", i.e. deleting one
   * specific contribution?
   *
   * @var boolean
   */
  protected $_single = FALSE;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    //check for delete
    if (!CRM_Core_Permission::checkActionPermission('CiviContribute', CRM_Core_Action::DELETE)) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this page.'));
    }
    parent::preProcess();
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $count = 0;
    if (CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus()) {
      foreach ($this->_contributionIds as $key => $id) {
        $finTypeID = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $id, 'financial_type_id');
        if (!CRM_Core_Permission::check('delete contributions of type ' . CRM_Contribute_PseudoConstant::financialType($finTypeID))) {
          unset($this->_contributionIds[$key]);
          $count++;
        }
        // Now check for lineItems
        if ($lineItems = CRM_Price_BAO_LineItem::getLineItemsByContributionID($id)) {
          foreach ($lineItems as $items) {
            if (!CRM_Core_Permission::check('delete contributions of type ' . CRM_Contribute_PseudoConstant::financialType($items['financial_type_id']))) {
              unset($this->_contributionIds[$key]);
              $count++;
              break;
            }
          }
        }
      }
    }
    if ($count && empty($this->_contributionIds)) {
      CRM_Core_Session::setStatus(ts('1 contribution could not be deleted.', array('plural' => '%count contributions could not be deleted.', 'count' => $count)), ts('Error'), 'error');
      $this->addButtons(array(
        array(
          'type' => 'back',
          'name' => ts('Cancel'),
        ),
       )
      );
    }
    elseif ($count && !empty($this->_contributionIds)) {
      CRM_Core_Session::setStatus(ts('1 contribution will not be deleted.', array('plural' => '%count contributions will not be deleted.', 'count' => $count)), ts('Warning'), 'warning');
      $this->addDefaultButtons(ts('Delete Contributions'), 'done');
    }
    else {
      $this->addDefaultButtons(ts('Delete Contributions'), 'done');
    }
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    $deleted = $failed = 0;
    foreach ($this->_contributionIds as $contributionId) {
      if (CRM_Contribute_BAO_Contribution::deleteContribution($contributionId)) {
        $deleted++;
      }
      else {
        $failed++;
      }
    }

    if ($deleted) {
      $msg = ts('%count contribution deleted.', array('plural' => '%count contributions deleted.', 'count' => $deleted));
      CRM_Core_Session::setStatus($msg, ts('Removed'), 'success');
    }

    if ($failed) {
      CRM_Core_Session::setStatus(ts('1 could not be deleted.', array('plural' => '%count could not be deleted.', 'count' => $failed)), ts('Error'), 'error');
    }
  }

}
