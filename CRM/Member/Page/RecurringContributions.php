<?php

/**
 * Shows list of recurring contributions related to membership.
 */
class CRM_Member_Page_RecurringContributions extends CRM_Core_Page {

  /**
   * ID of the membership for which we need to see related recurring contributions.
   *
   * @var int
   */
  private $membershipID = NULL;

  /**
   * ID of the contact owner of the membership.
   *
   * @var int
   */
  public $contactID = NULL;

  /**
   * Builds list of recurring contributions associated to membership.
   *
   * @return null
   */
  public function run() {
    $this->membershipID = CRM_Utils_Request::retrieve('membershipID', 'Positive', $this);
    $this->contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);

    $this->loadRecurringContributions();

    return parent::run();
  }

  /**
   * Loads recurring contributions and assigns them to the form, to be used on
   * the template.
   */
  private function loadRecurringContributions() {
    $recurringContributions = $this->getRecurContributions($this->membershipID);

    if (!empty($recurringContributions)) {
      $this->assign('recurRows', $recurringContributions);
      $this->assign('recur', TRUE);
    }
  }

  /**
   * Obtains list of recurring contributions associated to a membership.
   *
   * @param int $membershipID
   *
   * @return array
   */
  private function getRecurContributions($membershipID) {
    $result = civicrm_api3('MembershipPayment', 'get', [
      'sequential' => 1,
      'contribution_id.contribution_recur_id.id' => ['IS NOT NULL' => TRUE],
      'options' => ['limit' => 0],
      'return' => [
        'contribution_id.contribution_recur_id.id',
        'contribution_id.contribution_recur_id.contact_id',
        'contribution_id.contribution_recur_id.start_date',
        'contribution_id.contribution_recur_id.end_date',
        'contribution_id.contribution_recur_id.next_sched_contribution_date',
        'contribution_id.contribution_recur_id.amount',
        'contribution_id.contribution_recur_id.currency',
        'contribution_id.contribution_recur_id.frequency_unit',
        'contribution_id.contribution_recur_id.frequency_interval',
        'contribution_id.contribution_recur_id.installments',
        'contribution_id.contribution_recur_id.contribution_status_id',
        'contribution_id.contribution_recur_id.is_test',
        'contribution_id.contribution_recur_id.payment_processor_id',
      ],
      'membership_id' => $membershipID,
    ]);
    $recurringContributions = [];

    foreach ($result['values'] as $payment) {
      $recurringContributionID = (int) $payment['contribution_id.contribution_recur_id.id'];
      $alreadyProcessed = isset($recurringContributions[$recurringContributionID]);

      if ($alreadyProcessed) {
        continue;
      }

      foreach ($payment as $field => $value) {
        $key = strtr($field, ['contribution_id.contribution_recur_id.' => '']);
        $recurringContributions[$recurringContributionID][$key] = $value;
      }

      $contactID = $recurringContributions[$recurringContributionID]['contact_id'];
      $contributionStatusID = $recurringContributions[$recurringContributionID]['contribution_status_id'];

      $recurringContributions[$recurringContributionID]['id'] = $recurringContributionID;
      $recurringContributions[$recurringContributionID]['contactId'] = $contactID;
      $recurringContributions[$recurringContributionID]['contribution_status'] = CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_ContributionRecur', 'contribution_status_id', $contributionStatusID);

      $this->setActionsForRecurringContribution($recurringContributionID, $recurringContributions[$recurringContributionID]);
    }
    return $recurringContributions;
  }

  /**
   * Calculates and assigns the actions available for given recurring
   * contribution.
   *
   * @param int $recurID
   * @param array $recurringContribution
   */
  private function setActionsForRecurringContribution(int $recurID, &$recurringContribution) {
    $action = array_sum(array_keys(CRM_Contribute_Page_Tab::recurLinks($recurID, 'contribution')));

    // no action allowed if it's not active
    $recurringContribution['is_active'] = ($recurringContribution['contribution_status_id'] != 3);

    if ($recurringContribution['is_active']) {
      $details = CRM_Contribute_BAO_ContributionRecur::getSubscriptionDetails($recurringContribution['id']);
      $hideUpdate = $details->membership_id & $details->auto_renew;

      if ($hideUpdate) {
        $action -= CRM_Core_Action::UPDATE;
      }

      $recurringContribution['action'] = CRM_Core_Action::formLink(
        CRM_Contribute_Page_Tab::recurLinks($recurID),
        $action,
        [
          'cid' => $this->contactID,
          'crid' => $recurID,
          'cxt' => 'contribution',
        ],
        ts('more'),
        FALSE,
        'contribution.selector.recurring',
        'Contribution',
        $recurID
      );
    }
  }

}
