<?php

use CRM_Event_Cart_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Event_Cart_Upgrader extends CRM_Extension_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * This runs just before AutomaticUpgrader->uninstall.
   */
  public function uninstall(): void {
    // This column was originally part of core. It should not exist, but just to be on the safe side...
    \CRM_Core_BAO_SchemaHandler::safeRemoveFK('civicrm_participant', 'FK_civicrm_participant_cart_id');
  }

}
