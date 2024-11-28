<?php

require_once 'civi_member.civix.php';

/**
 * Implements hook_civicrm_check().
 *
 * Check for any legacy data where there is a membership_payment record but not a matching line item
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_check/
 */
function civi_member_civicrm_check(&$messages) :void {
  $membershipPayments = CRM_Core_DAO::executeQuery("SELECT contribution_id, membership_id FROM civicrm_membership_payment");
  $lineItemsMissingPayments = [];
  while ($membershipPayments->fetch()) {
    $lineItemCheck = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_line_item WHERE contribution_id = %1 AND entity_id = %2 AND entity_table = 'civicrm_membership'", [
      1 => [$membershipPayments->contribution_id, 'Positive'],
      2 => [$membershipPayments->membership_id, 'Positive'],
    ]);
    if (empty($lineItemCheck)) {
      $lineItemsMissingPayments[] = [
        'contribution_id' => $membershipPayments->contribution_id,
        'membership_id' => $membershipPayments->membership_id,
      ];
    }
  }
  if (!empty($lineItemsMissingPayments)) {
    $strings = '';
    foreach ($lineItemsMissingPayments as $lineItemsMissingPayment) {
      $strings .= '<tr><td>'. $lineItemsMissingPayment['contribution_id'] . '</td><td>' .  $lineItemsMissingPayment['membership_id']  . '</td></tr>';
    }
    $messages[] = new CRM_Utils_Check_Message(
      'civi_member_membership_payments_missing',
      ts('The Following Membership Payments do not have a corresponding line item record linking the contribution to membership.') . ts('This should be corrected either by updating the relevant line item record or adding a line item record as appropriate.') . '</p>
        <p></p><table><thead><th>Contribution ID</th><th>Membership ID</th></thead><tbody>' . $strings  . '</tbody></table></p>',
      ts('CiviCRM Membership Payment Records not matching'),
      \Psr\Log\LogLevel::WARNING,
      'fa-database',
    );
  }
}
