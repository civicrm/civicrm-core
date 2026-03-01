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
    $contributionRecurIds = \Civi\Api4\LineItem::get(TRUE)
      ->addWhere('entity_table', '=', 'civicrm_membership')
      ->addWhere('entity_id', '=', $membershipID)
      ->addWhere('contribution_id.contribution_recur_id', 'IS NOT NULL')
      ->addSelect('contribution_id.contribution_recur_id')
      ->execute()
      ->column('contribution_id.contribution_recur_id');

    // also include where the contribution is linked by legacy civicrm_membership_payment table
    if (\Civi::settings()->get('civi_member_use_civicrm_membership_payment_table')) {
      $contributionRecurIds = array_merge($contributionRecurIds, $this->getLegacyRecurContributionIds($membershipID, $contributionRecurIds));
    }

    $recurringContributions = (array) \Civi\Api4\ContributionRecur::get(FALSE)
      ->addWhere('id', 'IN', $contributionRecurIds)
      ->addSelect('*', 'contribution_status_id:label')
      ->indexBy('id')
      ->execute();

    $recurringContributions = array_map(function ($record) {
      // add legacy keys
      $record['contactId'] = $record['contact_id'];
      $record['contribution_status'] = $record['contribution_status_id:label'];
      // add actions
      $this->setActionsForRecurringContribution($record['id'], $record);
      return $record;
    }, $recurringContributions);

    return $recurringContributions;
  }

  private function getLegacyRecurContributionIds($membershipID, array $alreadyFound) {
    $result = civicrm_api3('MembershipPayment', 'get', [
      'sequential' => 1,
      'contribution_id.contribution_recur_id.id' => ['IS NOT NULL' => TRUE],
      'contribution_id.contribution_recur_id.id' => ['IS NOT IN' => $alreadyFound],
      'options' => ['limit' => 0],
      'return' => [
        'contribution_id.contribution_recur_id.id',
      ],
      'membership_id' => $membershipID,
    ]);

    return array_map(fn ($payment) => (int) $payment['contribution_id.contribution_recur_id.id'], $result['values']);
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
