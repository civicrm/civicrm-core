<?php

require_once 'contributioncancelactions.civix.php';
// phpcs:disable
use CRM_Contributioncancelactions_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_post
 */
function contributioncancelactions_civicrm_post($op, $objectName, $objectId, $objectRef) {
  if ($op === 'edit' && $objectName === 'Contribution') {
    if ($objectRef->contribution_status_id === CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Cancelled')) {
      // Do the stuff.
    }
  }
}
