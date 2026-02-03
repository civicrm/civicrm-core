<?php
namespace Civi\Membership;

use Civi\Api4\Activity;
use Civi\Api4\Contribution;
use Civi\Api4\LineItem;
use Civi\Api4\Membership;
use Civi\Api4\MembershipType;
use Civi\Core\Service\AutoService;
use Civi\Core\Service\IsActiveTrait;
use Civi\Order\Event\OrderCompleteEvent;
use CRM_Utils_Date;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class OrderCompleteSubscriber
 * @package Civi\Membership
 * @service civi_membership_order_complete
 *
 * This class provides the default behaviour for updating memberships on completion of contribution (On "Order Complete")
 */
class OrderCompleteSubscriber extends AutoService implements EventSubscriberInterface {

  use IsActiveTrait;

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents(): array {
    return [
      'civi.order.complete' => ['onOrderComplete', 0],
    ];
  }

  /**
   * Default handler for Membership on "Order Complete"
   * Note that the "civi.order.complete" will trigger for all "Completed orders"
   *   so you should check if there is actually a membership to update.
   *
   * @param \Civi\Order\Event\OrderCompleteEvent $event
   */
  public function onOrderComplete(OrderCompleteEvent $event): void {
    if (!$this->isActive()) {
      return;
    }

    try {
      self::updateMembershipBasedOnCompletionOfContribution($event->contributionID, $event->params['effective_date'] ?? NULL);
    }
    catch (\Exception $e) {
      \Civi::log()->error('civi_membership_order_complete: Error updating membership for contributionID: ' . $event->contributionID . ': ' . $e->getMessage());
    }
  }

  /**
   * Update the memberships associated with a contribution if it has been completed.
   *
   * Note that the way in which $memberships are loaded as objects is pretty messy & I think we could just
   * load them in this function. Code clean up would compensate for any minor performance implication.
   *
   * @param int $contributionID
   *   The Contribution ID that was Completed
   * @param string|null $changeDate
   *   If provided, specify an alternative date to use as "today" calculation of membership dates
   *
   * @return void
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  private static function updateMembershipBasedOnCompletionOfContribution(int $contributionID, ?string $changeDate) {

    $memberships = self::getRelatedMemberships($contributionID);
    if (empty($memberships)) {
      return;
    }

    $contribution = Contribution::get(FALSE)
      ->addWhere('id', '=', $contributionID)
      ->execute()
      ->first();
    foreach ($memberships as $membership) {
      $priorMembershipStatus = $membership['status_id:name'];
      $membershipParams = [
        'id' => $membership['id'],
        'contact_id' => $membership['contact_id'],
        'is_test' => $membership['is_test'],
        'membership_type_id' => $membership['membership_type_id'],
        'membership_activity_status' => 'Completed',
      ];

      if (!empty($contribution['contribution_recur_id'])) {
        $membershipParams['contribution_recur_id'] = $contribution['contribution_recur_id'];
      }

      // Update the membership type with the LineItem membership_type_id for potential membership type changes during renewals
      $membershipLineItem = LineItem::get(FALSE)
        ->addSelect('price_field_value.membership_type_id', 'membership_num_terms')
        ->addJoin('PriceFieldValue AS price_field_value', 'LEFT')
        ->addWhere('contribution_id', '=', $contributionID)
        ->addWhere('entity_table', '=', 'civicrm_membership')
        ->addWhere('contribution_id.contact_id', '=', $membershipParams['contact_id'])
        ->execute()
        ->first();
      if (!empty($membershipLineItem) && !empty($membershipLineItem['price_field_value.membership_type_id'])) {
        // If type is changed, reset properties to match.
        if ($membershipParams['membership_type_id'] !== $membershipLineItem['price_field_value.membership_type_id']) {
          $membershipType = MembershipType::get(FALSE)
            ->addWhere('id', '=', $membershipLineItem['price_field_value.membership_type_id'])
            ->execute()
            ->first();
          $membershipParams['max_related'] = $membershipType['max_related'] ?? NULL;
          $membershipParams['source'] = $contribution['source'] ?? $membership['source'];
        }
        $membershipParams['membership_type_id'] = $membershipLineItem['price_field_value.membership_type_id'];
      }
      if (empty($membership['end_date']) || (int) $membership['status_id'] !== \CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'status_id', 'Pending')) {
        // Passing num_terms to the api triggers date calculations, but for pending memberships these may be already calculated.
        // sigh - they should  be  consistent but removing the end date check causes test failures & maybe UI too?
        // The api assumes num_terms is a special sauce for 'is_renewal' so we need to not pass it when updating a pending to completed.
        // ... except testCompleteTransactionMembershipPriceSetTwoTerms hits this line so the above is obviously not true....

        // default of 1 is precautionary
        $membershipParams['num_terms'] = empty($membershipLineItem['membership_num_terms']) ? 1 : $membershipLineItem['membership_num_terms'];
      }

      if ('Pending' === $membership['status_id:name']) {
        $membershipParams['skipStatusCal'] = '';
      }
      else {
        // @todo remove all this stuff in favour of letting the api call further down handle in
        // (it is a duplication of what the api does).
        /*
         * Fixed FOR CRM-4433
         * In BAO/Membership.php(renewMembership function), we skip the extend membership date and status
         * when Contribution mode is notify and membership is for renewal
         */
        // Test cover for this is in testRepeattransactionRenewMembershipOldMembership
        // Be afraid.
        \CRM_Member_BAO_Membership::fixMembershipStatusBeforeRenew($membership, $changeDate);

        // @todo - we should pass membership_type_id instead of null here but not
        // adding as not sure of testing
        $dates = \CRM_Member_BAO_MembershipType::getRenewalDatesForMembershipType($membershipParams['id'],
          $changeDate, NULL, $membershipParams['num_terms']
        );
        $dates['join_date'] = $membership['join_date'];
        //get the status for membership.
        $calcStatus = \CRM_Member_BAO_MembershipStatus::getMembershipStatusByDate($dates['start_date'] ?? NULL,
          $dates['end_date'] ?? NULL,
          $dates['join_date'] ?? NULL,
          'now',
          TRUE,
          $membershipParams['membership_type_id'],
          $membershipParams
        );

        unset($dates['end_date']);
        $membershipParams['status_id'] = $calcStatus['id'] ?? 'New';

        //set the log start date.
        $membershipParams['log_start_date'] = CRM_Utils_Date::customFormat($dates['log_start_date'], '%Y%m%d');
      }
      //we might be renewing membership,
      //so make status override false.
      $membershipParams['is_override'] = FALSE;
      $membershipParams['status_override_end_date'] = 'null';
      $membership = civicrm_api3('Membership', 'create', $membershipParams);
      $membership = $membership['values'][$membership['id']];
      // Update activity to Completed.
      // Perhaps this should be in Membership::create? Test cover in
      // api_v3_ContributionTest.testPendingToCompleteContribution.
      Activity::update(FALSE)->setValues([
        'status_id:name' => 'Completed',
        'subject' => ts('Status changed from %1 to %2'), [
          1 => $priorMembershipStatus,
          2 => \CRM_Core_PseudoConstant::getLabel('CRM_Member_BAO_Membership', 'status_id', $membership['status_id']),
        ],

      ])->addWhere('source_record_id', '=', $membership['id'])
        ->addWhere('status_id:name', '=', 'Scheduled')
        ->addWhere('activity_type_id:name', 'IN', ['Membership Signup', 'Membership Renewal'])
        ->execute();
    }
  }

  /**
   * Get memberships related to the contribution.
   *
   * @param int $contributionID
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  private static function getRelatedMemberships(int $contributionID): array {
    $membershipIDs = array_keys((array) LineItem::get(FALSE)
      ->addWhere('contribution_id', '=', $contributionID)
      ->addWhere('entity_table', '=', 'civicrm_membership')
      ->addSelect('entity_id')
      ->execute()->indexBy('entity_id'));

    $membershipIDs = \CRM_Member_BAO_MembershipPayment::getMembershipPaymentsWithMissingLineitems($contributionID, $membershipIDs);

    if (empty($membershipIDs)) {
      return [];
    }
    return (array) Membership::get(FALSE)->addWhere('id', 'IN', $membershipIDs)
      ->addSelect('*', 'status_id:name')
      ->execute();
  }

}
