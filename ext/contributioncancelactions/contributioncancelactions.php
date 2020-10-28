<?php

require_once 'contributioncancelactions.civix.php';
// phpcs:disable
use CRM_Contributioncancelactions_ExtensionUtil as E;
// phpcs:enable
use Civi\Api4\LineItem;

/**
 * Implements hook_civicrm_preProcess().
 *
 * This enacts the following
 * - find and cancel any related pending memberships
 * - (not yet implemented) find and cancel any related pending participant records
 * - (not yet implemented) find any related pledge payment records. Remove the contribution id.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_post
 */
function contributioncancelactions_civicrm_post($op, $objectName, $objectId, $objectRef) {
  if ($op === 'edit' && $objectName === 'Contribution') {
    if ('Cancelled' === CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $objectRef->contribution_status_id)) {
      // Find and cancel any pending memberships.
      $connectedMemberships = (array) LineItem::get(FALSE)->setWhere([
        ['contribution_id', '=', $objectId],
        ['entity_table', '=', 'civicrm_membership'],
      ])->execute()->indexBy('entity_id');
      if (empty($connectedMemberships)) {
        return;
      }
      // @todo we don't have v4 membership api yet so v3 for now.
      $connectedMemberships = array_keys(civicrm_api3('Membership', 'get', [
        'status_id' => 'Pending',
        'id' => ['IN' => array_keys($connectedMemberships)],
      ])['values']);
      if (empty($connectedMemberships)) {
        return;
      }
      foreach ($connectedMemberships as $membershipID) {
        civicrm_api3('Membership', 'create', ['status_id' => 'Cancelled', 'id' => $membershipID, 'is_override' => 1]);
      }
    }
  }
}
