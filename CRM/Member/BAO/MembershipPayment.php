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

use Civi\Api4\LineItem;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Member_BAO_MembershipPayment extends CRM_Member_DAO_MembershipPayment {

  /**
   * Add the membership Payments.
   *
   * @param array $params
   *   Reference array contains the values submitted by the form.
   *
   *
   * @return object
   */
  public static function create($params) {
    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'MembershipPayment', $params['id'] ?? NULL, $params);
    $dao = new CRM_Member_DAO_MembershipPayment();
    $dao->copyValues($params);
    // We check for membership_id in case we are being called too early in the process. This is
    // cludgey but is part of the deprecation process (ie. we are trying to do everything
    // from LineItem::create with a view to eventually removing this fn & the table.
    if (!civicrm_api3('Membership', 'getcount', ['id' => $params['membership_id']])) {
      return $dao;
    }

    //Fixed for avoiding duplicate entry error when user goes
    //back and forward during payment mode is notify
    if (!$dao->find(TRUE)) {
      $dao->save();
    }
    CRM_Utils_Hook::post($hook, 'MembershipPayment', $dao->id, $dao);
    // CRM-14197 we are in the process on phasing out membershipPayment in favour of storing both contribution_id & entity_id (membership_id) on the line items
    // table. However, at this stage we have both - there is still quite a bit of refactoring to do to set the line_iten entity_id right the first time
    // however, we can assume at this stage that any contribution id will have only one line item with that membership type in the line item table
    // OR the caller will have taken responsibility for updating the line items themselves so we will update using SQL here
    if (!empty($params['isSkipLineItem'])) {
      // Caller has taken responsibility for updating the line items.
      return $dao;
    }
    if (!isset($params['membership_type_id'])) {
      $membership_type_id = civicrm_api3('membership', 'getvalue', [
        'id' => $dao->membership_id,
        'return' => 'membership_type_id',
      ]);
    }
    else {
      $membership_type_id = $params['membership_type_id'];
    }
    $sql = "UPDATE civicrm_line_item li
      LEFT JOIN civicrm_price_field_value pv ON pv.id = li.price_field_value_id
      SET entity_table = 'civicrm_membership', entity_id = %1
      WHERE pv.membership_type_id = %2
      AND contribution_id = %3";
    CRM_Core_DAO::executeQuery($sql, [
      1 => [$dao->membership_id, 'Integer'],
      2 => [$membership_type_id, 'Integer'],
      3 => [$dao->contribution_id, 'Integer'],
    ]);
    return $dao;
  }

  /**
   * Delete membership Payments.
   *
   * @param int $id
   * @deprecated
   * @return bool
   */
  public static function del($id) {
    CRM_Core_Error::deprecatedFunctionWarning('deleteRecord');
    return (bool) self::deleteRecord(['id' => $id]);
  }

  /**
   * Log a deprecated warning that there is a contribution with MembershipPayment records and missing LineItems
   *
   * @param int $contributionID
   *
   * @return void
   */
  private static function deprecatedWarning(int $contributionID) {
    CRM_Core_Error::deprecatedWarning('ContributionID: ' . $contributionID . ' has memberships with MembershipPayment records but missing LineItems. MembershipPayment records are deprecated.');
  }

  /**
   * Given a Membership ID we should be able to get the latest Contribution ID from the LineItems
   * But we might not have LineItems, in which case we try to get it from the MembershipPayment record
   *   if that exists and log a deprecation warning
   *
   * @param int $membershipID
   *
   * @return ?int
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   * @internal
   */
  public static function getLatestContributionIDFromLineitemAndFallbackToMembershipPayment(int $membershipID) {
    $latestMembershipLineItem = LineItem::get(FALSE)
      ->addSelect('contribution_id')
      ->addWhere('entity_table', '=', 'civicrm_membership')
      ->addWhere('entity_id', '=', $membershipID)
      ->addOrderBy('contribution_id.receive_date', 'DESC')
      ->execute()
      ->first();
    if (!empty($latestMembershipLineItem['contribution_id'])) {
      $latestContributionID = $latestMembershipLineItem['contribution_id'];
    }
    else {
      $membershipPayments = civicrm_api3('MembershipPayment', 'get', [
        'sequential' => 1,
        'return' => ["contribution_id.receive_date", "contribution_id"],
        'membership_id' => $membershipID,
        'options' => ['sort' => "contribution_id.receive_date DESC"],
      ])['values'];
      if (!empty($membershipPayments[0]['contribution_id'])) {
        $latestContributionID = $membershipPayments[0]['contribution_id'];
        self::deprecatedWarning($latestContributionID);
      }
    }
    return $latestContributionID ?? NULL;
  }

}
