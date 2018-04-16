<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
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

    $this->loadRelatedContributions();
  }

  /**
   * Loads contributions associated to the current recurring contribution being
   * viewed.
   */
  private function loadRelatedContributions() {
    $relatedContributions = array();

    $relatedContributionsResult = civicrm_api3('Contribution', 'get', array(
      'sequential' => 1,
      'contribution_recur_id' => $this->_id,
      'contact_id' => $this->_contactId,
      'options' => array('limit' => 0),
    ));

    foreach ($relatedContributionsResult['values'] as $contribution) {
      $this->insertAmountExpandingPaymentsControl($contribution);
      $this->fixDateFormats($contribution);
      $this->insertStatusLabels($contribution);
      $this->insertContributionActions($contribution);

      $relatedContributions[] = $contribution;
    }

    if (count($relatedContributions) > 0) {
      $this->assign('relatedContributions', json_encode($relatedContributions));
    }
  }

  /**
   * Inserts a string into the array with the html used to show the expanding
   * payments control, which loads when user clicks on the amount.
   *
   * @param array $contribution
   *   Reference to the array holding the contribution's data and where the
   *   control will be inserted into
   */
  private function insertAmountExpandingPaymentsControl(&$contribution) {
    $amount = CRM_Utils_Money::format($contribution['total_amount'], $contribution['currency']);

    $expandPaymentsUrl = CRM_Utils_System::url('civicrm/payment',
      array(
        'view' => 'transaction',
        'component' => 'contribution',
        'action' => 'browse',
        'cid' => $this->_contactId,
        'id' => $contribution['contribution_id'],
        'selector' => 1,
      ),
      FALSE, NULL, TRUE
    );

    $contribution['amount_control'] = '
      <a class="nowrap bold crm-expand-row" title="view payments" href="' . $expandPaymentsUrl . '">
        &nbsp; ' . $amount . '
      </a>
    ';
  }

  /**
   * Fixes date fields present in the given contribution.
   *
   * @param array $contribution
   *   Reference to the array holding the contribution's data
   */
  private function fixDateFormats(&$contribution) {
    $config = CRM_Core_Config::singleton();

    $contribution['formatted_receive_date'] = CRM_Utils_Date::customFormat($contribution['receive_date'], $config->dateformatDatetime);
    $contribution['formatted_thankyou_date'] = CRM_Utils_Date::customFormat($contribution['thankyou_date'], $config->dateformatDatetime);
  }

  /**
   * Inserts a contribution_status_label key into the array, with the value
   * showing the current status plus observations on the current status.
   *
   * @param array $contribution
   *   Reference to the array holding the contribution's data and where the new
   *   position will be inserted
   */
  private function insertStatusLabels(&$contribution) {
    $contribution['contribution_status_label'] = $contribution['contribution_status'];

    if ($contribution['is_pay_later'] && CRM_Utils_Array::value('contribution_status', $contribution) == 'Pending') {
      $contribution['contribution_status_label'] .= ' (' . ts('Pay Later') . ')';
    }
    elseif (CRM_Utils_Array::value('contribution_status', $contribution) == 'Pending') {
      $contribution['contribution_status_label'] .= ' (' . ts('Incomplete Transaction') . ')';
    }
  }

  /**
   * Inserts into the given array a string with the 'action' key, holding the
   * html to be used to show available actions for the contribution.
   *
   * @param $contribution
   *   Reference to the array holding the contribution's data. It is also the
   *   array where the new 'action' key will be inserted.
   */
  private function insertContributionActions(&$contribution) {
    $contribution['action'] = CRM_Core_Action::formLink(
      $this->buildContributionLinks($contribution),
      $this->getContributionPermissionsMask(),
      array(
        'id' => $contribution['contribution_id'],
        'cid' => $contribution['contact_id'],
        'cxt' => 'contribution',
      ),
      ts('more'),
      FALSE,
      'contribution.selector.row',
      'Contribution',
      $contribution['contribution_id']
    );
  }

  /**
   * Builds list of links for authorized actions that can be done on given
   * contribution.
   *
   * @param array $contribution
   *
   * @return array
   */
  private function buildContributionLinks($contribution) {
    $links = CRM_Contribute_Selector_Search::links($contribution['contribution_id'],
      CRM_Utils_Request::retrieve('action', 'String'),
      NULL,
      NULL
    );

    $isPayLater = FALSE;
    if ($contribution['is_pay_later'] && CRM_Utils_Array::value('contribution_status', $contribution) == 'Pending') {
      $isPayLater = TRUE;

      $links[CRM_Core_Action::ADD] = array(
        'name' => ts('Pay with Credit Card'),
        'url' => 'civicrm/contact/view/contribution',
        'qs' => 'reset=1&action=update&id=%%id%%&cid=%%cid%%&context=%%cxt%%&mode=live',
        'title' => ts('Pay with Credit Card'),
      );
    }

    if (in_array($contribution['contribution_status'], array('Partially paid', 'Pending refund')) || $isPayLater) {
      $buttonName = ts('Record Payment');

      if ($contribution['contribution_status'] == 'Pending refund') {
        $buttonName = ts('Record Refund');
      }
      elseif (CRM_Core_Config::isEnabledBackOfficeCreditCardPayments()) {
        $links[CRM_Core_Action::BASIC] = array(
          'name' => ts('Submit Credit Card payment'),
          'url' => 'civicrm/payment/add',
          'qs' => 'reset=1&id=%%id%%&cid=%%cid%%&action=add&component=contribution&mode=live',
          'title' => ts('Submit Credit Card payment'),
        );
      }
      $links[CRM_Core_Action::ADD] = array(
        'name' => $buttonName,
        'url' => 'civicrm/payment',
        'qs' => 'reset=1&id=%%id%%&cid=%%cid%%&action=add&component=contribution',
        'title' => $buttonName,
      );
    }

    return $links;
  }

  /**
   * Builds a mask with allowed contribution related permissions.
   *
   * @return int
   */
  private function getContributionPermissionsMask() {
    $permissions = array(CRM_Core_Permission::VIEW);
    if (CRM_Core_Permission::check('edit contributions')) {
      $permissions[] = CRM_Core_Permission::EDIT;
    }
    if (CRM_Core_Permission::check('delete in CiviContribute')) {
      $permissions[] = CRM_Core_Permission::DELETE;
    }

    return CRM_Core_Action::mask($permissions);
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
