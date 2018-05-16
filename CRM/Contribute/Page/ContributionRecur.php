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
 * Main page for viewing Recurring Contributions.
 */
class CRM_Contribute_Page_ContributionRecur extends CRM_Core_Page {

  static $_links = NULL;
  public $_permission = NULL;
  public $_contactId = NULL;
  public $_id = NULL;
  public $_action = NULL;

  /**
   * View details of a recurring contribution.
   */
  public function view() {
    if (empty($this->_id)) {
      CRM_Core_Error::statusBounce('Recurring contribution not found');
    }

    try {
      $contributionRecur = civicrm_api3('ContributionRecur', 'getsingle', array(
        'id' => $this->_id,
      ));
    }
    catch (Exception $e) {
      CRM_Core_Error::statusBounce('Recurring contribution not found (ID: ' . $this->_id);
    }

    $contributionRecur['payment_processor'] = CRM_Financial_BAO_PaymentProcessor::getPaymentProcessorName(
      CRM_Utils_Array::value('payment_processor_id', $contributionRecur)
    );
    $idFields = array('contribution_status_id', 'campaign_id', 'financial_type_id');
    foreach ($idFields as $idField) {
      if (!empty($contributionRecur[$idField])) {
        $contributionRecur[substr($idField, 0, -3)] = CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_ContributionRecur', $idField, $contributionRecur[$idField]);
      }
    }

    // Add linked membership
    $membership = civicrm_api3('Membership', 'get', array(
      'contribution_recur_id' => $contributionRecur['id'],
    ));
    if (!empty($membership['count'])) {
      $membershipDetails = reset($membership['values']);
      $contributionRecur['membership_id'] = $membershipDetails['id'];
      $contributionRecur['membership_name'] = $membershipDetails['membership_name'];
    }

    $groupTree = CRM_Core_BAO_CustomGroup::getTree('ContributionRecur', NULL, $contributionRecur['id']);
    CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $groupTree, FALSE, NULL, NULL, NULL, $contributionRecur['id']);

    $this->assign('recur', $contributionRecur);
  }

  public function preProcess() {
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'view');
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    $this->assign('contactId', $this->_contactId);

    // check logged in url permission
    CRM_Contact_Page_View::checkUserPermission($this);

    $this->assign('action', $this->_action);

    if ($this->_permission == CRM_Core_Permission::EDIT && !CRM_Core_Permission::check('edit contributions')) {
      // demote to view since user does not have edit contrib rights
      $this->_permission = CRM_Core_Permission::VIEW;
      $this->assign('permission', 'view');
    }
  }

  /**
   * the main function that is called when the page loads,
   * it decides the which action has to be taken for the page.
   *
   * @return null
   */
  public function run() {
    $this->preProcess();

    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->view();
    }

    return parent::run();
  }

}
