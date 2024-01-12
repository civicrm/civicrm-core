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
 * Main page for viewing Recurring Contributions.
 */
class CRM_Contribute_Page_ContributionRecur extends CRM_Core_Page {

  use CRM_Core_Page_EntityPageTrait;

  /**
   * @return string
   */
  public function getDefaultEntity() {
    return 'ContributionRecur';
  }

  protected function getDefaultAction() {
    return 'view';
  }

  /**
   * View details of a recurring contribution.
   */
  public function view() {
    if (empty($this->getEntityId())) {
      CRM_Core_Error::statusBounce(ts('Recurring contribution not found'));
    }

    try {
      $contributionRecur = civicrm_api3('ContributionRecur', 'getsingle', [
        'id' => $this->getEntityId(),
      ]);
    }
    catch (Exception $e) {
      CRM_Core_Error::statusBounce(ts('Recurring contribution not found (ID: %1)', [1 => $this->getEntityId()]));
    }

    $contributionRecur['payment_processor'] = CRM_Financial_BAO_PaymentProcessor::getPaymentProcessorName(
      $contributionRecur['payment_processor_id'] ?? NULL
    );
    $idFields = ['contribution_status_id', 'campaign_id', 'financial_type_id'];
    foreach ($idFields as $idField) {
      if (!empty($contributionRecur[$idField])) {
        $contributionRecur[substr($idField, 0, -3)] = CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_ContributionRecur', $idField, $contributionRecur[$idField]);
      }
    }

    // Add linked membership
    $membership = civicrm_api3('Membership', 'get', [
      'contribution_recur_id' => $contributionRecur['id'],
    ]);
    if (!empty($membership['count'])) {
      $membershipDetails = reset($membership['values']);
      $contributionRecur['membership_id'] = $membershipDetails['id'];
      $contributionRecur['membership_name'] = $membershipDetails['membership_name'];
    }

    $groupTree = CRM_Core_BAO_CustomGroup::getTree('ContributionRecur', NULL, $contributionRecur['id'], NULL, [],
      NULL, TRUE, NULL, FALSE, CRM_Core_Permission::VIEW);
    CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $groupTree, FALSE, NULL, NULL, NULL, $contributionRecur['id']);

    if (isset($contributionRecur['trxn_id']) && ($contributionRecur['processor_id'] === $contributionRecur['trxn_id'])) {
      unset($contributionRecur['trxn_id']);
    }
    $this->assign('recur', $contributionRecur);

    $templateContribution = CRM_Contribute_BAO_ContributionRecur::getTemplateContribution($this->getEntityId());

    $lineItems = [];
    $displayLineItems = FALSE;
    if (!empty($templateContribution['id'])) {
      $lineItems = [CRM_Price_BAO_LineItem::getLineItemsByContributionID(($templateContribution['id']))];
      $displayLineItems = TRUE;
    }
    $this->assign('lineItem', $lineItems);
    $this->assign('displayLineItems', $displayLineItems);

    $displayName = CRM_Contact_BAO_Contact::displayName($contributionRecur['contact_id']);
    $this->assign('displayName', $displayName);

    // Check if this is default domain contact CRM-10482
    if (CRM_Contact_BAO_Contact::checkDomainContact($contributionRecur['contact_id'])) {
      $displayName .= ' (' . ts('default organization') . ')';
    }

    // omitting contactImage from title for now since the summary overlay css doesn't work outside of our crm-container
    CRM_Utils_System::setTitle(ts('View Recurring Contribution from') . ' ' . $displayName);
  }

  public function preProcess() {
    $this->preProcessQuickEntityPage();
  }

  /**
   * the main function that is called when the page loads,
   * it decides the which action has to be taken for the page.
   *
   * @return null
   */
  public function run() {
    $this->preProcess();
    $this->assign('hasAccessCiviContributePermission', CRM_Core_Permission::check('access CiviContribute'));
    if ($this->isViewContext()) {
      $this->view();
    }

    return parent::run();
  }

}
