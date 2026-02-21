<?php

require_once 'civi_event.civix.php';

/**
 * Implements hook_civicrm_check().
 *
 * Check for any legacy data where there is a participant_payment record but not a matching line item
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_check/
 */
function civi_event_civicrm_check(&$messages) :void {
  $parcitipantPayments = CRM_Core_DAO::executeQuery("SELECT contribution_id, participant_id FROM civicrm_participant_payment");
  $lineItemsMissingPayments = [];
  while ($parcitipantPayments->fetch()) {
    $lineItemCheck = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_line_item WHERE contribution_id = %1 AND entity_id = %2 AND entity_table = 'civicrm_participant'", [
      1 => [$parcitipantPayments->contribution_id, 'Positive'],
      2 => [$parcitipantPayments->participant_id, 'Positive'],
    ]);
    if (empty($lineItemCheck)) {
      $lineItemsMissingPayments[] = [
        'contribution_id' => $parcitipantPayments->contribution_id,
        'participant_id' => $parcitipantPayments->participant_id,
      ];
    }
  }
  if (!empty($lineItemsMissingPayments)) {
    $strings = '';
    foreach ($lineItemsMissingPayments as $lineItemsMissingPayment) {
      $strings .= '<tr><td>'. $lineItemsMissingPayment['contribution_id'] . '</td><td>' .  $lineItemsMissingPayment['participant_id']  . '</td></tr>';
    }
    $messages[] = new CRM_Utils_Check_Message(
      'civi_event_participant_payments_missing',
      ts('The Following Participant Payments do not have a corresponding line item record linking the contribution to participant.') . ts('This should be corrected either by updating the relevant line item record or adding a line item record as appropriate.') . '</p>
        <p></p><table><thead><th>Contribution ID</th><th>Participant ID</th></thead><tbody>' . $strings  . '</tbody></table></p>',
      ts('CiviCRM Participant Payment Records not matching'),
      \Psr\Log\LogLevel::WARNING,
      'fa-database',
    );
  }
}
